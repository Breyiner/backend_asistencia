<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceDashboardService
{
    public function get(array $filters): array
    {
        $userId = (int) Auth::id();
        $roleCode = (string) request()->attributes->get('acting_role_code');

        [$from, $to, $preset] = $this->resolveRange($filters);

        $visibleFichaIds = $this->visibleFichaIds($userId, $roleCode);
        $visibleFichaIds = $this->applyFichaFilters($visibleFichaIds, $filters);

        if (empty($visibleFichaIds)) {
            return $this->emptyResponse($from, $to, $preset);
        }

        $kpis = $this->kpis($visibleFichaIds, $from, $to);
        $pie  = $this->pieStatus($visibleFichaIds, $from, $to);
        $bars = $this->barsByDay($visibleFichaIds, $from, $to);
        $top  = $this->topFichasAbsences($visibleFichaIds, $from, $to);

        // Riesgo SOLO si ficha_id está seleccionada
        $risk = [];
        $dropoutAlertCount = 0;

        $selectedFichaId = isset($filters['ficha_id']) ? (int) $filters['ficha_id'] : null;
        if ($selectedFichaId) {
            // seguridad: ficha seleccionada debe estar en el scope visible
            if (!in_array($selectedFichaId, $visibleFichaIds, true)) {
                abort(403, 'No tienes acceso a esa ficha.');
            }

            $risk = $this->riskByFichaCurrentTerm($selectedFichaId);
            $dropoutAlertCount = count($risk);
        }

        // pisa el KPI de alerta con la regla que pediste
        $kpis['dropout_alert_count'] = (int) $dropoutAlertCount;

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Dashboard generado correctamente',
            'meta' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
                'training_program_id' => $filters['training_program_id'] ?? null,
                'ficha_id' => $selectedFichaId,
            ],
            'data' => [
                'kpis' => $kpis,
                'pie_status' => $pie,
                'bars_by_day' => $bars,
                'top_fichas_absences' => $top,
                'risk_apprentices' => $risk,
            ],
        ];
    }

    /* ---------------- Range ---------------- */
    private function resolveRange(array $filters): array
    {
        $today = Carbon::now()->toDateString();
        $preset = $filters['preset'] ?? '7d';

        if ($preset === 'month') {
            return [Carbon::now()->startOfMonth()->toDateString(), $today, 'month'];
        }

        if ($preset === '30d') {
            return [Carbon::now()->subDays(29)->toDateString(), $today, '30d'];
        }

        if ($preset === 'custom') {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            if (!$from || !$to) abort(422, 'from y to son requeridos para rango personalizado');
            // YYYY-MM-DD (HTML date) [web:782]
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                abort(422, 'Formato de fecha inválido. Use YYYY-MM-DD');
            }
            if ($from > $to) abort(422, 'from no puede ser mayor que to');

            $maxDays = 62;
            $diff = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
            if ($diff > $maxDays) abort(422, "El rango máximo permitido es {$maxDays} días");

            return [$from, $to, 'custom'];
        }

        // default 7d
        return [Carbon::now()->subDays(6)->toDateString(), $today, '7d'];
    }

    /* ---------------- Scope por rol ---------------- */
    private function visibleFichaIds(int $userId, string $roleCode): array
    {
        $q = DB::table('fichas')->where('status_id', 1);

        if ($roleCode === 'INSTRUCTOR') {
            // trimestre actual + schedule sessions del instructor (misma lógica que tu FichaService) [file:851]
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
            $q->where('gestor_id', $userId);
        } elseif ($roleCode === 'COORDINADOR') {
            $q->whereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('training_programs as tp')
                    ->whereColumn('tp.id', 'fichas.training_program_id')
                    ->where('tp.coordinator_id', $userId);
            });
        }
        // ADMIN: todo (solo activas)

        return $q->pluck('id')->map(fn ($id) => (int) $id)->toArray();
    }

    private function applyFichaFilters(array $visibleFichaIds, array $filters): array
    {
        if (empty($visibleFichaIds)) return [];

        $trainingProgramId = $filters['training_program_id'] ?? null;
        $fichaId = $filters['ficha_id'] ?? null;

        if (!$trainingProgramId && !$fichaId) return $visibleFichaIds;

        $q = DB::table('fichas')->whereIn('id', $visibleFichaIds);

        if ($trainingProgramId) $q->where('training_program_id', (int) $trainingProgramId);
        if ($fichaId) $q->where('id', (int) $fichaId);

        return $q->pluck('id')->map(fn ($id) => (int) $id)->toArray();
    }

    /* ---------------- Agregados por rango ---------------- */
    private function baseAttendancesQuery(array $fichaIds, string $from, string $to)
    {
        // Nota: apprentice = users (extendido), status_id=1 activos.
        return DB::table('attendances as a')
            ->join('real_classes as rc', 'rc.id', '=', 'a.real_class_id')
            ->join('users as u', 'u.id', '=', 'a.apprentice_id')
            ->join('attendance_statuses as s', 's.id', '=', 'a.attendance_status_id')
            ->whereIn('u.ficha_id', $fichaIds)
            ->where('u.status_id', 1)
            ->whereBetween('rc.execution_date', [$from, $to]);
    }

    private function kpis(array $fichaIds, string $from, string $to): array
    {
        $totalAprendices = DB::table('users')
            ->whereIn('ficha_id', $fichaIds)
            ->where('status_id', 1)
            ->count();

        $base = $this->baseAttendancesQuery($fichaIds, $from, $to);
        $totalMarks = (clone $base)->count();
        $presentMarks = (clone $base)->where('s.code', 'present')->count();

        return [
            'total_apprentices' => (int) $totalAprendices,
            'total_present_marks' => (int) $presentMarks,
            'attendance_avg_pct' => $totalMarks ? round(($presentMarks / $totalMarks) * 100, 1) : 0.0,
            'dropout_alert_count' => 0, // se setea arriba solo si ficha_id
        ];
    }

    private function pieStatus(array $fichaIds, string $from, string $to): array
    {
        $rows = $this->baseAttendancesQuery($fichaIds, $from, $to)
            ->selectRaw('s.code as code, COUNT(*) as total')
            ->groupBy('s.code')
            ->get();

        $total = (int) $rows->sum('total');
        $map = $rows->pluck('total', 'code')->toArray();

        $codes = ['present', 'absent', 'excused_absence', 'late', 'early_exit', 'unregistered'];
        $out = [];

        foreach ($codes as $code) {
            $count = (int) ($map[$code] ?? 0);
            if ($count === 0) continue;
            $out[] = [
                'code' => $code,
                'count' => $count,
                'pct' => $total ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $out;
    }

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
            'date' => $r->date,
            'present' => (int) $r->present_count,
            'absent' => (int) $r->absent_count,
        ])->values()->toArray();
    }

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
            'ficha_id' => (int) $r->ficha_id,
            'ficha_number' => $r->ficha_number,
            'training_program_name' => $r->training_program_name,
            'absences' => (int) $r->absences,
        ])->values()->toArray();
    }

    /* ---------------- Riesgo: fijo al trimestre actual de una ficha ---------------- */
    private function riskByFichaCurrentTerm(int $fichaId): array
    {
        // 1) Obtener el trimestre actual y su rango de fechas
        $term = DB::table('ficha_terms')
            ->where('ficha_id', $fichaId)
            ->where('is_current', 1)
            ->select('id', 'start_date', 'end_date')
            ->first();

        if (!$term || !$term->start_date || !$term->end_date) {
            // Si no hay rango de fechas definido, puedes decidir: devolver vacío o derivarlo por clases.
            return [];
        }

        $from = (string) $term->start_date;
        $to = (string) $term->end_date;

        // 2) Query de métricas (usa code='absent')
        $sql = "
            WITH ficha_apprentices AS (
                SELECT u.id AS apprentice_id
                FROM users u
                WHERE u.status_id = 1 AND u.ficha_id = :ficha_id
            ),
            daily AS (
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
                SELECT apprentice_id, the_date
                FROM daily
                WHERE absences_in_day >= 1
            ),
            ranked AS (
                SELECT
                    apprentice_id,
                    the_date,
                    ROW_NUMBER() OVER (PARTITION BY apprentice_id ORDER BY the_date) AS rn
                FROM absent_days
            ),
            streaks AS (
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
            WHERE (m.max_consecutive_absent_days >= 3 OR m.total_absent_days >= 5)
            ORDER BY m.max_consecutive_absent_days DESC, m.total_absent_days DESC
        ";

        $rows = DB::select($sql, [
            'ficha_id' => $fichaId,
            'from' => $from,
            'to' => $to,
        ]);

        if (empty($rows)) return [];

        $apprenticeIds = array_map(fn($r) => (int) $r->apprentice_id, $rows);

        // 3) Fechas de ausencia dentro del trimestre (para el modal)
        $absentDates = DB::table('attendances as a')
            ->join('real_classes as rc', 'rc.id', '=', 'a.real_class_id')
            ->join('attendance_statuses as s', 's.id', '=', 'a.attendance_status_id')
            ->whereIn('a.apprentice_id', $apprenticeIds)
            ->whereBetween('rc.execution_date', [$from, $to])
            ->where('s.code', 'absent')
            ->select('a.apprentice_id', 'rc.execution_date')
            ->distinct()
            ->orderBy('rc.execution_date')
            ->get()
            ->groupBy('apprentice_id')
            ->map(fn($g) => $g->pluck('execution_date')->values()->toArray())
            ->toArray();

        // 4) Datos del aprendiz (ajusta joins si tu nombre real es profiles)
        $users = DB::table('users as u')
            ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
            ->selectRaw('u.id, u.document_number, p.first_name, p.last_name')
            ->whereIn('u.id', $apprenticeIds)
            ->get()
            ->keyBy('id');

        return array_map(function ($r) use ($users, $absentDates) {
            $id = (int) $r->apprentice_id;
            $u = $users[$id] ?? null;
            $name = $u ? trim(($u->first_name ?? '').' '.($u->last_name ?? '')) : 'Sin nombre';

            return [
                'apprentice_id' => $id,
                'name' => $name,
                'document_number' => $u->document_number ?? null,
                'total_absent_days' => (int) $r->total_absent_days,
                'max_consecutive_absent_days' => (int) $r->max_consecutive_absent_days,
                'dropout_alert' => (int) $r->dropout_alert,
                'absence_dates_in_current_term' => $absentDates[$id] ?? [],
                'current_term_range' => ['from' => $GLOBALS['__tmp_from'] ?? null, 'to' => $GLOBALS['__tmp_to'] ?? null], // opcional; puedes quitarlo
            ];
        }, $rows);
    }

    private function emptyResponse(string $from, string $to, string $preset): array
    {
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Sin datos para el scope actual',
            'meta' => compact('from', 'to', 'preset'),
            'data' => [
                'kpis' => [
                    'total_apprentices' => 0,
                    'total_present_marks' => 0,
                    'attendance_avg_pct' => 0.0,
                    'dropout_alert_count' => 0,
                ],
                'pie_status' => [],
                'bars_by_day' => [],
                'top_fichas_absences' => [],
                'risk_apprentices' => [],
            ],
        ];
    }
}