<?php

namespace App\Policies;

use App\Models\ScheduleSession;
use App\Models\User;

/**
 * Policy de autorización para el modelo RealClass (Clase Real).
 *
 * Controla quién puede crear clases reales en el sistema.
 * Las reglas de acceso varían según el rol activo del usuario:
 *
 * - ADMIN: puede crear cualquier clase real (bypass total)
 * - INSTRUCTOR: solo puede crear clases de sesiones donde es el instructor asignado
 * - GESTOR_FICHAS: solo puede crear clases de fichas que gestiona
 * - Otros roles: sin acceso
 *
 * Importante: Esta policy usa permisos del ROL ACTIVO (actuando),
 * no del usuario directamente. El rol activo se obtiene del request
 * via middleware, permitiendo que usuarios con múltiples roles
 * actúen bajo un rol específico.
 *
 * Registro en AuthServiceProvider:
 * RealClass::class => RealClassPolicy::class
 *
 * Uso en controlador:
 * $this->authorize('create', [$scheduleSession, $instructorId]);
 */
class RealClassPolicy
{
    /**
     * Intercepta todas las verificaciones de la policy (gate before hook).
     *
     * Se ejecuta ANTES que cualquier método específico de la policy.
     * Permite hacer verificaciones globales que aplican a todas las acciones.
     *
     * Lógica:
     * - Si el rol activo NO tiene permiso 'real_classes.create' → deniega siempre (false)
     * - Si el rol activo ES ADMIN → aprueba siempre (true)
     * - Cualquier otro caso → delega al método específico (null)
     *
     * Retornos:
     * - false: deniega sin pasar al método específico
     * - true: aprueba sin pasar al método específico
     * - null: continúa evaluando el método específico (create, update, etc.)
     *
     * @param User   $user    Usuario autenticado
     * @param string $ability Nombre de la acción ('create', 'update', etc.)
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        // Obtiene los permisos del rol activo desde atributos del request
        // Este array es inyectado por middleware de autenticación por rol
        $actingPerms = request()?->attributes?->get('acting_role_permissions', []);

        // Obtiene el código del rol activo (ej: 'ADMIN', 'INSTRUCTOR')
        $actingCode  = request()?->attributes?->get('acting_role_code');

        // Si el rol activo no tiene el permiso base de crear clases reales,
        // deniega inmediatamente sin evaluar más lógica
        if (!in_array('real_classes.create', $actingPerms, true)) {
            return false;
        }

        // El ADMIN tiene acceso total: aprueba sin más verificaciones
        if ($actingCode === 'ADMIN') {
            return true;
        }

        // Retorna null para que Laravel evalúe el método específico (create)
        return null;
    }

    /**
     * Determina si el usuario puede crear una clase real para una sesión específica.
     *
     * Se ejecuta solo cuando before() retorna null (roles no-ADMIN con permiso).
     *
     * Validaciones comunes (todos los roles):
     * 1. Tiene el permiso 'real_classes.create' en su rol activo
     * 2. La sesión pertenece a un trimestre y ficha válidos
     * 3. El trimestre debe ser el actual (is_current = true)
     *
     * Validaciones por rol:
     * - INSTRUCTOR:
     *   - instructorId no puede ser null
     *   - El instructorId debe coincidir con el instructor de la sesión
     *   - El usuario autenticado debe ser el instructor de la sesión
     * - GESTOR_FICHAS:
     *   - El usuario autenticado debe ser el gestor de la ficha
     *
     * @param User            $user            Usuario autenticado
     * @param ScheduleSession $scheduleSession  Sesión de horario a la que pertenece la clase
     * @param int|null        $instructorId     ID del instructor (requerido para rol INSTRUCTOR)
     * @return bool true si puede crear, false si no
     */
    public function create(User $user, ScheduleSession $scheduleSession, ?int $instructorId = null): bool
    {
        // Obtiene permisos y código del rol activo del request
        $actingPerms = request()?->attributes?->get('acting_role_permissions', []);
        $actingCode  = request()?->attributes?->get('acting_role_code');

        // Verifica nuevamente el permiso base (por si se llama directamente sin before)
        if (!in_array('real_classes.create', $actingPerms, true)) {
            return false;
        }

        // Navega la cadena de relaciones para obtener el trimestre y la ficha
        // ScheduleSession → Schedule → FichaTerm → Ficha
        $fichaTerm = $scheduleSession->schedule?->fichaTerm;
        $ficha     = $fichaTerm?->ficha;

        // Si no se puede determinar el trimestre o la ficha, deniega
        if (!$fichaTerm || !$ficha) return false;

        // Solo se pueden crear clases en el trimestre activo (is_current = true)
        // Evita que se creen clases en trimestres pasados o futuros
        if (!(bool) $fichaTerm->is_current) return false;

        /**
         * VALIDACIÓN PARA ROL INSTRUCTOR.
         *
         * El instructor solo puede crear clases de sus propias sesiones:
         * 1. instructorId debe ser enviado en la petición
         * 2. instructorId debe coincidir con el instructor asignado a la sesión
         * 3. El usuario autenticado debe ser el mismo instructor de la sesión
         */
        if ($actingCode === 'INSTRUCTOR') {
            // El instructorId es obligatorio para instructores
            if ($instructorId === null) return false;

            // El instructorId enviado debe coincidir con el de la sesión
            // (evita que un instructor cree clases en nombre de otro)
            if ((int) $instructorId !== (int) $scheduleSession->instructor_id) return false;

            // El usuario autenticado debe ser el instructor asignado a la sesión
            return (int) $user->id === (int) $scheduleSession->instructor_id;
        }

        /**
         * VALIDACIÓN PARA ROL GESTOR DE FICHAS.
         *
         * El gestor solo puede crear clases en fichas que gestiona directamente.
         */
        if ($actingCode === 'GESTOR_FICHAS') {
            // El usuario autenticado debe ser el gestor asignado a la ficha
            return (int) $user->id === (int) $ficha->gestor_id;
        }

        // Cualquier otro rol no tiene acceso
        return false;
    }
}