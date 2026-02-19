<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para generar el dashboard de asistencias.
 *
 * Consolida KPIs, gráficas y alertas de riesgo de deserción filtrando
 * por rango de fechas y scope de fichas según el rol del usuario autenticado.
 *
 * El acceso a fichas está restringido por rol:
 * - ADMIN: todas las fichas activas.
 * - COORDINADOR: fichas de sus programas de formación.
 * - GESTOR_FICHAS: fichas donde es gestor.
 * - INSTRUCTOR: fichas donde tiene sesiones en el trimestre actual.
 */
class AttendanceDashboardService
{
    /**
     * Genera el dashboard completo de asistencias para el usuario autenticado.
     *
     * Flujo principal:
     * 1. Resuelve el rango de fechas según el preset o rango personalizado.
     * 2. Determina las fichas visibles según el rol.
     * 3. Aplica filtros adicionales de programa o ficha específica.
     * 4. Calcula KPIs, gráfica de torta, barras por día y top de inasistencias.
     * 5. Si se seleccionó una ficha específica, calcula también el riesgo de deserción.
     *
     * @param  array  $filters  Filtros del request: preset, from, to, training_program_id, ficha_id.
     * @return array
     */
    public function get(array $filters): array
    {
        $userId   = (int) Auth::id();
        $roleCode = (string) request()->attributes->get('acting_role_code');

        // Resuelve el rango de fechas [from, to] y el nombre del preset aplicado.
        [$from, $to, $preset] = $this->resolveRange($filters);

        // Obtiene las fichas visibles para el rol y luego aplica filtros adicionales.
        $visibleFichaIds = $this->visibleFichaIds($userId, $roleCode);
        $visibleFichaIds = $this->applyFichaFilters($visibleFichaIds, $filters);

        // Si no hay fichas en el scope (ej: rol sin fichas asignadas), retorna vacío.
        if (empty($visibleFichaIds)) {
            return $this->emptyResponse($from, $to, $preset);
        }

        // Calcula los 4 bloques de datos del dashboard en consultas independientes.
        $kpis = $this->kpis($visibleFichaIds, $from, $to);
        $pie  = $this->pieStatus($visibleFichaIds, $from, $to);
        $bars = $this->barsByDay($visibleFichaIds, $from, $to);
        $top  = $this->topFichasAbsences($visibleFichaIds, $from, $to);

        // El análisis de riesgo de deserción solo aplica cuando se filtra por una ficha específica,
        // ya que requiere calcular racha de ausencias consecutivas por aprendiz.
        $risk              = [];
        $dropoutAlertCount = 0;

        $selectedFichaId = isset($filters['ficha_id']) ? (int) $filters['ficha_id'] : null;
        if ($selectedFichaId) {
            // Valida que la ficha seleccionada esté dentro del scope visible del rol.
            // Evita que un usuario acceda a datos de una ficha que no le corresponde.
            if (!in_array($selectedFichaId, $visibleFichaIds, true)) {
                abort(403, 'No tienes acceso a esa ficha.');
            }

            $risk              = $this->riskByFichaCurrentTerm($selectedFichaId);
            $dropoutAlertCount = count($risk);
        }

        // Sobreescribe el KPI de alerta con el conteo real (calculado arriba o 0 si no aplica).
        $kpis['dropout_alert_count'] = (int) $dropoutAlertCount;

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Dashboard generado correctamente',
            'meta'    => [
                'preset'              => $preset,
                'from'                => $from,
                'to'                  => $to,
                'training_program_id' => $filters['training_program_id'] ?? null,
                'ficha_id'            => $selectedFichaId,
            ],
            'data' => [
                'kpis'                => $kpis,
                'pie_status'          => $pie,
                'bars_by_day'         => $bars,
                'top_fichas_absences' => $top,
                'risk_apprentices'    => $risk,
            ],
        ];
    }

    /* =========================================================================
     * RANGO DE FECHAS
     * ========================================================================= */

    /**
     * Resuelve el rango de fechas [from, to, preset] según el filtro recibido.
     *
     * Presets soportados:
     * - 7d (default): últimos 7 días incluyendo hoy.
     * - 30d: últimos 30 días incluyendo hoy.
     * - month: desde el primer día del mes actual hasta hoy.
     * - custom: rango libre entre from y to (máximo 62 días).
     *
     * @param  array  $filters
     * @return array  [from, to, preset] todos como string Y-m-d.
     */
    private function resolveRange(array $filters): array
    {
        $today  = Carbon::now()->toDateString();
        $preset = $filters['preset'] ?? '7d';

        if ($preset === 'month') {
            // Desde el inicio del mes hasta hoy.
            return [Carbon::now()->startOfMonth()->toDateString(), $today, 'month'];
        }

        if ($preset === '30d') {
            // subDays(29) + hoy = 30 días en total.
            return [Carbon::now()->subDays(29)->toDateString(), $today, '30d'];
        }

        if ($preset === 'custom') {
            $from = $filters['from'] ?? null;
            $to   = $filters['to'] ?? null;

            if (!$from || !$to) abort(422, 'from y to son requeridos para rango personalizado');

            // Valida el formato antes de parsear para evitar fechas inválidas silenciosas.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                abort(422, 'Formato de fecha inválido. Use YYYY-MM-DD');
            }

            if ($from > $to) abort(422, 'from no puede ser mayor que to');

            // Límite de 62 días para evitar queries demasiado pesadas.
            $maxDays = 62;
            $diff = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
            if ($diff > $maxDays) abort(422, "El rango máximo permitido es {$maxDays} días");

            return [$from, $to, 'custom'];
        }

        // Default: últimos 7 días (subDays(6) + hoy = 7 días).
        return [Carbon::now()->subDays(6)->toDateString(), $today, '7d'];
    }

    /* =========================================================================
     * SCOPE POR ROL
     * ========================================================================= */

    /**
     * Retorna los IDs de fichas activas visibles para el usuario según su rol.
     *
     * Usa EXISTS en lugar de JOINs para no inflar filas cuando hay múltiples
     * sesiones o programas relacionados con la misma ficha.
     *
     * @param  int     $userId    ID del usuario autenticado.
     * @param  string  $roleCode  Código del rol activo (ADMIN, COORDINADOR, etc.).
     * @return array   IDs de fichas visibles como enteros.
     */
    private function visibleFichaIds(int $userId, string $roleCode): array
    {
        // Base: solo fichas activas (status_id = 1).
        $q = DB::table('fichas')->where('status_id', 1);

        if ($roleCode === 'INSTRUCTOR') {
            // El instructor solo ve fichas donde tiene sesiones en el trimestre actual.
            $q->whereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('ficha_terms as ft')
                    ->join('schedules as sch', 'sch.ficha_term_id', '=', 'ft.id')
                    ->join('schedule_sessions as ss', 'ss.schedule_id', '=', 'sch.id')
                    ->whereColumn('ft.ficha_id', 'fichas.id')
                    ->where('ft.is_current', 1)
                    ->where('ss.instructor_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            // El gestor solo ve las fichas que gestiona directamente.
            $q->where('gestor_id', $userId);
        } elseif ($roleCode === 'COORDINADOR') {
            // El coordinador ve las fichas de todos sus programas de formación.
            $q->whereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('training_programs as tp')
                    ->whereColumn('tp.id', 'fichas.training_program_id')
                    ->where('tp.coordinator_id', $userId);
            });
        }
        // ADMIN: sin restricciones adicionales; ve todas las fichas activas.

        return $q->pluck('id')->map(fn ($id) => (int) $id)->toArray();
    }

    /**
     * Aplica filtros adicionales de programa o ficha sobre las fichas ya visibles.
     *
     * Siempre opera dentro del scope visible del rol; nunca amplía el acceso.
     *
     * @param  array  $visibleFichaIds  IDs de fichas permitidas por el rol.
     * @param  array  $filters          Filtros del request (training_program_id, ficha_id).
     * @return array  IDs filtrados.
     */
    private function applyFichaFilters(array $visibleFichaIds, array $filters): array
    {
        if (empty($visibleFichaIds)) return [];

        $trainingProgramId = $filters['training_program_id'] ?? null;
        $fichaId           = $filters['ficha_id'] ?? null;

        // Si no hay filtros adicionales, retorna el scope completo sin tocar la BD.
        if (!$trainingProgramId && !$fichaId) return $visibleFichaIds;

        // Filtra dentro del scope visible; whereIn garantiza que no se salga del scope.
        $q = DB::table('fichas')->whereIn('id', $visibleFichaIds);

        if ($trainingProgramId) $q->where('training_program_id', (int) $trainingProgramId);
        if ($fichaId) $q->where('id', (int) $fichaId);

        return $q->pluck('id')->map(fn ($id) => (int) $id)->toArray();
    }

    /* =========================================================================
     * QUERIES BASE Y AGREGADOS
     * ========================================================================= */

    /**
     * Construye la query base de asistencias filtrada por fichas y rango de fechas.
     *
     * Se reutiliza como punto de partida para todos los agregados del dashboard,
     * evitando duplicar los JOINs y condiciones en cada método.
     * Solo incluye aprendices activos (status_id = 1).
     *
     * @param  array   $fichaIds  IDs de fichas del scope.
     * @param  string  $from      Fecha inicio (Y-m-d).
     * @param  string  $to        Fecha fin (Y-m-d).
     * @return \Illuminate\Database\Query\Builder
     */
    private function baseAttendancesQuery(array $fichaIds, string $from, string $to)
    {
        return DB::table('attendances as a')
            ->join('real_classes as rc', 'rc.id', '=', 'a.real_class_id')
            ->join('users as u', 'u.id', '=', 'a.apprentice_id')
            ->join('attendance_statuses as s', 's.id', '=', 'a.attendance_status_id')
            ->whereIn('u.ficha_id', $fichaIds)
            ->where('u.status_id', 1)
            ->whereBetween('rc.execution_date', [$from, $to]);
    }

    /**
     * Calcula los KPIs principales del dashboard.
     *
     * Usa clone de la query base para reutilizar los JOINs sin re-ejecutar
     * la misma query completa varias veces.
     *
     * KPIs calculados:
     * - total_apprentices: aprendices activos en el scope (no depende del rango).
     * - total_present_marks: marcas con código 'present' en el rango.
     * - attendance_avg_pct: porcentaje de presencia sobre el total de marcas.
     * - dropout_alert_count: se inicializa en 0; se sobreescribe en get() si aplica.
     *
     * @param  array   $fichaIds
     * @param  string  $from
     * @param  string  $to
     * @return array
     */
    private function kpis(array $fichaIds, string $from, string $to): array
    {
        // Cuenta aprendices activos independientemente del rango de fechas.
        $totalAprendices = DB::table('users')
            ->whereIn('ficha_id', $fichaIds)
            ->where('status_id', 1)
            ->count();

        $base        = $this->baseAttendancesQuery($fichaIds, $from, $to);
        $totalMarks   = (clone $base)->count();
        $presentMarks = (clone $base)->where('s.code', 'present')->count();

        return [
            'total_apprentices'    => (int) $totalAprendices,
            'total_present_marks'  => (int) $presentMarks,
            // Evita división por cero si no hay marcas en el rango.
            'attendance_avg_pct'   => $totalMarks ? round(($presentMarks / $totalMarks) * 100, 1) : 0.0,
            'dropout_alert_count'  => 0, // Placeholder; se sobreescribe en get() si hay ficha_id.
        ];
    }

    /**
     * Calcula la distribución de asistencias por estado para la gráfica de torta.
     *
     * Omite estados con count = 0 para no enviar datos vacíos al frontend.
     * El porcentaje se redondea a entero para facilitar la visualización.
     *
     * @param  array   $fichaIds
     * @param  string  $from
     * @param  string  $to
     * @return array   Lista de {code, count, pct} sin estados con count = 0.
     */
    private function pieStatus(array $fichaIds, string $from, string $to): array
    {
        // Agrupa por código de estado y cuenta en una sola query.
        $rows = $this->baseAttendancesQuery($fichaIds, $from, $to)
            ->selectRaw('s.code as code, COUNT(*) as total')
            ->groupBy('s.code')
            ->get();

        $total = (int) $rows->sum('total');
        // Convierte a mapa code => total para acceso O(1) en el loop.
        $map = $rows->pluck('total', 'code')->toArray();

        $codes = ['present', 'absent', 'excused_absence', 'late', 'early_exit', 'unregistered'];
        $out   = [];

        foreach ($codes as $code) {
            $count = (int) ($map[$code] ?? 0);
            // Excluye estados sin registros para mantener la torta limpia.
            if ($count === 0) continue;
            $out[] = [
                'code'  => $code,
                'count' => $count,
                'pct'   => $total ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $out;
    }

    /**
     * Calcula los conteos de presentes y ausentes por día para la gráfica de barras.
     *
     * Usa SUM con expresión booleana en MySQL (code='present') como forma compacta
     * de hacer un pivot de dos estados en una sola query sin subconsultas.
     *
     * @param  array   $fichaIds
     * @param  string  $from
     * @param  string  $to
     * @return array   Lista de {date, present, absent} ordenada por fecha.
     */
    private function barsByDay(array $fichaIds, string $from, string $to): array
    {
        $rows = $this->baseAttendancesQuery($fichaIds, $from, $to)
            ->selectRaw("
                rc.execution_date as date,
                SUM(s.code='present') as present_count,
                SUM(s.code='absent') as absent_count
            ")
            ->groupBy('rc.execution_date')
            ->orderBy('rc.execution_date')
            ->get();

        return $rows->map(fn ($r) => [
            'date'    => $r->date,
            'present' => (int) $r->present_count,
            'absent'  => (int) $r->absent_count,
        ])->values()->toArray();
    }

    /**
     * Retorna el top 5 de fichas con más inasistencias en el rango.
     *
     * Útil para identificar rápidamente qué fichas requieren atención.
     *
     * @param  array   $fichaIds
     * @param  string  $from
     * @param  string  $to
     * @return array   Lista de {ficha_id, ficha_number, training_program_name, absences}.
     */
    private function topFichasAbsences(array $fichaIds, string $from, string $to): array
    {
        $rows = $this->baseAttendancesQuery($fichaIds, $from, $to)
            ->where('s.code', 'absent')
            ->join('fichas as f', 'f.id', '=', 'u.ficha_id')
            ->join('training_programs as tp', 'tp.id', '=', 'f.training_program_id')
            ->selectRaw('f.id as ficha_id, f.ficha_number, tp.name as training_program_name, COUNT(*) as absences')
            ->groupBy('f.id', 'f.ficha_number', 'tp.name')
            ->orderByDesc('absences')
            ->limit(5)
            ->get();

        return $rows->map(fn ($r) => [
            'ficha_id'              => (int) $r->ficha_id,
            'ficha_number'          => $r->ficha_number,
            'training_program_name' => $r->training_program_name,
            'absences'              => (int) $r->absences,
        ])->values()->toArray();
    }

    /* =========================================================================
     * RIESGO DE DESERCIÓN
     * ========================================================================= */

    /**
     * Calcula los aprendices en riesgo de deserción para el trimestre actual de una ficha.
     *
     * Un aprendiz se considera en riesgo si cumple al menos una de estas condiciones:
     * - Tiene 3 o más días de ausencia consecutivos.
     * - Tiene 5 o más días de ausencia en total en el trimestre.
     *
     * La query usa CTEs (WITH) para estructurar el cálculo en pasos:
     * 1. ficha_apprentices: aprendices activos de la ficha.
     * 2. daily: ausencias agrupadas por aprendiz y día.
     * 3. absent_days: días donde hubo al menos una ausencia.
     * 4. ranked: numera cada día de ausencia por aprendiz para detectar rachas.
     * 5. streaks: agrupa días consecutivos usando la técnica DATE - ROW_NUMBER.
     * 6. metrics: consolida total de días ausentes y racha máxima por aprendiz.
     *
     * La técnica DATE_SUB(date, INTERVAL rn DAY) genera un valor constante
     * para días que forman una racha consecutiva, permitiendo agruparlos con COUNT.
     *
     * @param  int  $fichaId  ID de la ficha seleccionada.
     * @return array  Lista de aprendices en riesgo con sus métricas y fechas de ausencia.
     */
    private function riskByFichaCurrentTerm(int $fichaId): array
    {
        // Paso 1: Obtiene el trimestre activo y su rango de fechas.
        $term = DB::table('ficha_terms')
            ->where('ficha_id', $fichaId)
            ->where('is_current', 1)
            ->select('id', 'start_date', 'end_date')
            ->first();

        // Si la ficha no tiene trimestre activo o le faltan fechas, no se puede calcular.
        if (!$term || !$term->start_date || !$term->end_date) {
            return [];
        }

        $from = (string) $term->start_date;
        $to   = (string) $term->end_date;

        // Paso 2: Ejecuta la query con CTEs para calcular métricas de riesgo.
        // Se usa SQL raw porque las CTEs con funciones de ventana (ROW_NUMBER, OVER)
        // no tienen soporte nativo en el Query Builder de Laravel.
        $sql = "
            WITH ficha_apprentices AS (
                SELECT u.id AS apprentice_id
                FROM users u
                WHERE u.status_id = 1 AND u.ficha_id = :ficha_id
            ),
            daily AS (
                -- Agrupa por aprendiz y día para no contar múltiples ausencias del mismo día.
                SELECT
                    a.apprentice_id,
                    rc.execution_date AS the_date,
                    SUM(s.code = 'absent') AS absences_in_day
                FROM attendances a
                JOIN real_classes rc ON rc.id = a.real_class_id
                JOIN attendance_statuses s ON s.id = a.attendance_status_id
                JOIN ficha_apprentices fa ON fa.apprentice_id = a.apprentice_id
                WHERE rc.execution_date BETWEEN :from AND :to
                GROUP BY a.apprentice_id, rc.execution_date
            ),
            absent_days AS (
                -- Solo los días donde efectivamente hubo ausencia.
                SELECT apprentice_id, the_date
                FROM daily
                WHERE absences_in_day >= 1
            ),
            ranked AS (
                -- Numera cada día de ausencia por aprendiz, ordenado por fecha.
                -- El número de fila (rn) se usa para detectar rachas en el siguiente CTE.
                SELECT
                    apprentice_id,
                    the_date,
                    ROW_NUMBER() OVER (PARTITION BY apprentice_id ORDER BY the_date) AS rn
                FROM absent_days
            ),
            streaks AS (
                -- Técnica: DATE_SUB(date, INTERVAL rn DAY) produce el mismo valor
                -- para días consecutivos, lo que permite agruparlos y contarlos.
                SELECT
                    apprentice_id,
                    COUNT(*) AS streak_days
                FROM (
                    SELECT
                        apprentice_id,
                        the_date,
                        DATE_SUB(the_date, INTERVAL rn DAY) AS grp
                    FROM ranked
                ) x
                GROUP BY apprentice_id, grp
            ),
            metrics AS (
                -- Consolida el total de días ausentes y la racha máxima por aprendiz.
                SELECT
                    fa.apprentice_id,
                    COALESCE((SELECT COUNT(*) FROM absent_days ad WHERE ad.apprentice_id = fa.apprentice_id), 0) AS total_absent_days,
                    COALESCE((SELECT MAX(s.streak_days) FROM streaks s WHERE s.apprentice_id = fa.apprentice_id), 0) AS max_consecutive_absent_days
                FROM ficha_apprentices fa
            )
            SELECT
                m.apprentice_id,
                m.total_absent_days,
                m.max_consecutive_absent_days,
                CASE
                    WHEN m.max_consecutive_absent_days >= 3 OR m.total_absent_days >= 5 THEN 1
                    ELSE 0
                END AS dropout_alert
            FROM metrics m
            -- Solo retorna aprendices que superan al menos uno de los umbrales de riesgo.
            WHERE (m.max_consecutive_absent_days >= 3 OR m.total_absent_days >= 5)
            ORDER BY m.max_consecutive_absent_days DESC, m.total_absent_days DESC
        ";

        $rows = DB::select($sql, [
            'ficha_id' => $fichaId,
            'from'     => $from,
            'to'       => $to,
        ]);

        if (empty($rows)) return [];

        $apprenticeIds = array_map(fn($r) => (int) $r->apprentice_id, $rows);

        // Paso 3: Carga las fechas exactas de ausencia de cada aprendiz en riesgo,
        // agrupadas por aprendiz para construir el detalle del modal en el frontend.
        $absentDates = DB::table('attendances as a')
            ->join('real_classes as rc', 'rc.id', '=', 'a.real_class_id')
            ->join('attendance_statuses as s', 's.id', '=', 'a.attendance_status_id')
            ->whereIn('a.apprentice_id', $apprenticeIds)
            ->whereBetween('rc.execution_date', [$from, $to])
            ->where('s.code', 'absent')
            ->select('a.apprentice_id', 'rc.execution_date')
            ->distinct() // Una fecha por aprendiz aunque haya múltiples clases en el día.
            ->orderBy('rc.execution_date')
            ->get()
            ->groupBy('apprentice_id')
            ->map(fn($g) => $g->pluck('execution_date')->values()->toArray())
            ->toArray();

        // Paso 4: Carga los datos personales de los aprendices en riesgo en una sola query.
        // keyBy('id') permite acceso O(1) al iterar los resultados del SQL.
        $users = DB::table('users as u')
            ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
            ->selectRaw('u.id, u.document_number, p.first_name, p.last_name')
            ->whereIn('u.id', $apprenticeIds)
            ->get()
            ->keyBy('id');

        // Paso 5: Combina los resultados del SQL con los datos del aprendiz y sus fechas.
        return array_map(function ($r) use ($users, $absentDates) {
            $id   = (int) $r->apprentice_id;
            $u    = $users[$id] ?? null;
            $name = $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) : 'Sin nombre';

            return [
                'apprentice_id'                  => $id,
                'name'                           => $name,
                'document_number'                => $u->document_number ?? null,
                'total_absent_days'              => (int) $r->total_absent_days,
                'max_consecutive_absent_days'    => (int) $r->max_consecutive_absent_days,
                'dropout_alert'                  => (int) $r->dropout_alert,
                'absence_dates_in_current_term'  => $absentDates[$id] ?? [],
                'current_term_range'             => ['from' => $GLOBALS['__tmp_from'] ?? null, 'to' => $GLOBALS['__tmp_to'] ?? null],
            ];
        }, $rows);
    }

    /* =========================================================================
     * RESPUESTA VACÍA
     * ========================================================================= */

    /**
     * Retorna una respuesta vacía con la estructura completa del dashboard.
     *
     * Se usa cuando el scope de fichas es vacío para que el frontend
     * reciba siempre la misma forma de datos sin necesidad de manejar null.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $preset
     * @return array
     */
    private function emptyResponse(string $from, string $to, string $preset): array
    {
        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Sin datos para el scope actual',
            'meta'    => compact('from', 'to', 'preset'),
            'data'    => [
                'kpis' => [
                    'total_apprentices'    => 0,
                    'total_present_marks'  => 0,
                    'attendance_avg_pct'   => 0.0,
                    'dropout_alert_count'  => 0,
                ],
                'pie_status'          => [],
                'bars_by_day'         => [],
                'top_fichas_absences' => [],
                'risk_apprentices'    => [],
            ],
        ];
    }
}