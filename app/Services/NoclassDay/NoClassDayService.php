<?php

namespace App\Services\NoClassDay;

use App\Events\ResourceChanged;
use App\Models\NoClassDay;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de días sin clase.
 *
 * Un día sin clase representa una fecha en la que una ficha no tiene actividad académica
 * (festivos, paros, actividades institucionales, etc.). Aplica filtros de visibilidad
 * por rol en los métodos de consulta, igual que en FichaService y ApprenticeService.
 */
class NoClassDayService
{
    /**
     * Retorna una lista paginada de días sin clase con filtros opcionales.
     *
     * Aplica restricciones de visibilidad según el rol activo:
     * - COORDINADOR: solo días de fichas en programas donde es coordinador.
     * - GESTOR_FICHAS: solo días de fichas donde es gestor.
     * - INSTRUCTOR: solo días de fichas con término activo donde tiene sesiones.
     * - ADMIN u otros: ve todos los registros sin restricción.
     *
     * Soporta filtros por fecha exacta, rango de fechas, ficha y motivo.
     *
     * @param  int  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Selecciona campos necesarios para el listado e incluye relaciones.
        $query = NoClassDay::select([
            'id',
            'ficha_id',
            'reason_id',
            'date',
            'observations',
            'created_at',
            'updated_at',
        ])->with([
            'ficha:id,ficha_number,training_program_id,shift_id,gestor_id',
            'ficha.trainingProgram:id,name',
            'reason:id,name,description',
        ]);

        // Restricciones de visibilidad según el rol activo.
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            // El instructor solo ve días de fichas con término activo donde tiene sesiones asignadas.
            $query->whereHas('ficha.currentFichaTerm', function ($q) use ($userId) {
                $q->where('is_current', 1)
                    ->whereHas('schedule.scheduleSessions', function ($subQ) use ($userId) {
                        $subQ->where('instructor_id', $userId);
                    });
            });
        }
        // ADMIN no tiene restricción de alcance; ve todos los registros.

        // Filtro exacto por ID de ficha.
        if (request()->filled('ficha_id')) {
            $query->where('ficha_id', request('ficha_id'));
        }

        // Filtro por número de ficha (búsqueda parcial).
        if (request()->filled('ficha_number')) {
            $query->whereHas('ficha', function ($q) {
                $q->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
            });
        }

        // Filtro exacto por ID de motivo.
        if (request()->filled('reason_id')) {
            $query->where('reason_id', request('reason_id'));
        }

        // Filtro por nombre de motivo (búsqueda parcial).
        if (request()->filled('reason_name')) {
            $query->whereHas('reason', function ($q) {
                $q->where('name', 'like', '%' . request('reason_name') . '%');
            });
        }

        // Filtro por fecha exacta; whereDate ignora la parte de hora si la columna es datetime.
        if (request()->filled('date')) {
            $query->whereDate('date', request('date'));
        }

        // Filtros de rango de fechas; pueden usarse juntos o por separado.
        if (request()->filled('date_from')) {
            $query->whereDate('date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('date', '<=', request('date_to'));
        }

        // Filtro por programa de formación al que pertenece la ficha.
        if (request()->filled('training_program_id')) {
            $query->whereHas('ficha', function ($q) {
                $q->where('training_program_id', request('training_program_id'));
            });
        }

        // Ordena por fecha descendente para mostrar los más recientes primero.
        $noClassDays = $query->orderBy('date', 'desc')->paginate($perPage);

        // Mapea cada registro a un array plano con los campos necesarios para la respuesta.
        $items = $noClassDays->getCollection()->map(function ($noClassDay) {
            return [
                'id' => $noClassDay->id,
                'date' => $noClassDay->date,

                'ficha_id' => $noClassDay->ficha_id,
                'ficha_number' => $noClassDay->ficha?->ficha_number ?? 'Sin ficha',

                'training_program_id' => $noClassDay->ficha?->training_program_id,
                'training_program_name' => $noClassDay->ficha?->trainingProgram?->name ?? 'Sin programa',

                'reason_id' => $noClassDay->reason_id,
                'reason_name' => $noClassDay->reason?->name ?? 'Sin motivo',
                'reason_description' => $noClassDay->reason?->description ?? '',

                'observations' => $noClassDay->observations,

                'created_at' => $noClassDay->created_at?->toDateString(),
                'updated_at' => $noClassDay->updated_at?->toDateString(),
            ];
        })->values();

        // Si no hay resultados, retorna mensaje informativo con paginación vacía.
        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay días sin clase registrados',
                'data' => [],
                'paginate' => [
                    'current_page' => $noClassDays->currentPage(),
                    'per_page' => $noClassDays->perPage(),
                    'total' => $noClassDays->total(),
                    'last_page' => $noClassDays->lastPage(),
                    'from' => $noClassDays->firstItem(),
                    'to' => $noClassDays->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Días sin clase obtenidos correctamente',
            'data' => $items,
            'paginate' => [
                'current_page' => $noClassDays->currentPage(),
                'per_page' => $noClassDays->perPage(),
                'total' => $noClassDays->total(),
                'last_page' => $noClassDays->lastPage(),
                'from' => $noClassDays->firstItem(),
                'to' => $noClassDays->lastItem(),
            ],
        ];
    }

    /**
     * Retorna el detalle completo de un día sin clase por su ID.
     *
     * Incluye más relaciones que getAll() (gestor, jornada) para mostrar
     * el contexto completo de la ficha a la que pertenece.
     * Aplica las mismas restricciones de visibilidad por rol.
     *
     * @param  mixed  $noClassDayId  ID del día sin clase.
     * @return array
     */
    public function getById($noClassDayId)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Carga más relaciones que getAll() para el detalle completo.
        $query = NoClassDay::select([
            'id',
            'ficha_id',
            'reason_id',
            'date',
            'observations',
            'created_at',
            'updated_at',
        ])->with([
            'ficha:id,ficha_number,training_program_id,shift_id,gestor_id',
            'ficha.trainingProgram:id,name,coordinator_id',
            'ficha.shift:id,name',
            'ficha.gestor.profile:id,user_id,first_name,last_name',
            'reason:id,name,description',
        ]);

        // Aplica las mismas restricciones de visibilidad por rol que en getAll().
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('ficha.currentFichaTerm', function ($q) use ($userId) {
                $q->where('is_current', 1)
                    ->whereHas('schedule.scheduleSessions', function ($subQ) use ($userId) {
                        $subQ->where('instructor_id', $userId);
                    });
            });
        }
        // ADMIN no tiene restricción de alcance.

        // Si no existe o el rol no tiene acceso, retorna 404.
        $noClassDay = $query->find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        // Construye el nombre del gestor fuera del array de respuesta para mayor legibilidad.
        $gestorName = $noClassDay->ficha?->gestor?->profile
            ? trim($noClassDay->ficha->gestor->profile->first_name . ' ' . $noClassDay->ficha->gestor->profile->last_name)
            : 'Sin gestor';

        $data = [
            'id' => $noClassDay->id,
            'date' => $noClassDay->date,

            'ficha_id' => $noClassDay->ficha_id,
            'ficha_number' => $noClassDay->ficha?->ficha_number ?? 'Sin ficha',

            'gestor_id' => $noClassDay->ficha?->gestor_id,
            'gestor_name' => $gestorName,

            'training_program_id' => $noClassDay->ficha?->training_program_id,
            'training_program_name' => $noClassDay->ficha?->trainingProgram?->name ?? 'Sin programa',

            'shift_id' => $noClassDay->ficha?->shift_id,
            'shift_name' => $noClassDay->ficha?->shift?->name ?? 'Sin jornada',

            'reason_id' => $noClassDay->reason_id,
            'reason_name' => $noClassDay->reason?->name ?? 'Sin motivo',
            'reason_description' => $noClassDay->reason?->description ?? '',

            'observations' => $noClassDay->observations,

            'created_at' => $noClassDay->created_at?->toDateString(),
            'updated_at' => $noClassDay->updated_at?->toDateString(),
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase obtenido correctamente',
            'data' => $data,
        ];
    }

    /**
     * Crea un nuevo día sin clase.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function store($data)
    {
        $created = NoClassDay::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            NoClassDay::class,
            $created->id,
            Auth::id(),
            'Día sin clase',
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Día sin clase creado correctamente',
            'data' => $created,
        ];
    }

    /**
     * Actualiza un día sin clase existente.
     *
     * Solo actualiza si hay campos válidos en $data. A diferencia de otros servicios,
     * si no hay campos para actualizar retorna éxito silencioso en lugar de error 400,
     * y el evento solo se dispara si efectivamente hubo cambios.
     *
     * @param  mixed  $noClassDayId  ID del día sin clase.
     * @param  array  $data          Campos a actualizar.
     * @return array
     */
    public function update($noClassDayId, $data)
    {
        $noClassDay = NoClassDay::find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $dataToUpdate = [];

        if (array_key_exists('ficha_id', $data)) $dataToUpdate['ficha_id'] = $data['ficha_id'];
        if (array_key_exists('date', $data)) $dataToUpdate['date'] = $data['date'];
        if (array_key_exists('reason_id', $data)) $dataToUpdate['reason_id'] = $data['reason_id'];
        if (array_key_exists('observations', $data)) $dataToUpdate['observations'] = $data['observations'];

        // Solo ejecuta el update y el evento si hay campos que cambiar.
        // Si no hay campos, retorna éxito con el registro sin modificar.
        if (!empty($dataToUpdate)) {
            $noClassDay->update($dataToUpdate);

            event(new ResourceChanged(
                'actualizar',
                NoClassDay::class,
                $noClassDay->id,
                Auth::id(),
                'Día sin clase',
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase actualizado correctamente',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $noClassDay->fresh(),
        ];
    }

    /**
     * Elimina un día sin clase por su ID.
     *
     * A diferencia de otros servicios, aquí se usa $noClassDay->id en el evento
     * porque el ID se guarda en variable local antes del delete().
     *
     * @param  mixed  $noClassDayId  ID del día sin clase.
     * @return array
     */
    public function destroy($noClassDayId)
    {
        $noClassDay = NoClassDay::find($noClassDayId);

        if (!$noClassDay) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día sin clase no encontrado',
                'data' => [],
            ];
        }

        $noClassDay->delete();

        // Nota: aquí se usa $noClassDay->id (no $noClassDayId) porque el modelo
        // fue cargado antes del delete y el ID sigue disponible en el objeto en memoria.
        event(new ResourceChanged(
            'eliminar',
            NoClassDay::class,
            $noClassDay->id,
            Auth::id(),
            'Día sin clase',
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día sin clase eliminado correctamente',
            'data' => [],
        ];
    }

    /**
     * Verifica si una fecha está marcada como día sin clase para una ficha específica.
     *
     * Útil para validar antes de crear una clase real en una fecha determinada.
     * Siempre retorna éxito; la información se devuelve en el campo is_no_class_day.
     *
     * @param  mixed   $fichaId  ID de la ficha.
     * @param  string  $date     Fecha a verificar en formato Y-m-d.
     * @return array
     */
    public function checkByFichaAndDate($fichaId, $date)
    {
        // exists() es eficiente para verificaciones booleanas; no trae el registro completo.
        $exists = NoClassDay::query()
            ->where('ficha_id', $fichaId)
            ->whereDate('date', $date)
            ->exists();

        return [
            'error' => false,
            'code' => 200,
            'message' => $exists
                ? 'La fecha está marcada como día sin clase.'
                : 'La fecha no está marcada como día sin clase.',
            'data' => [
                'ficha_id' => (int) $fichaId,
                'date' => $date,
                // El frontend puede evaluar este booleano directamente para bloquear acciones.
                'is_no_class_day' => $exists,
            ],
        ];
    }
}