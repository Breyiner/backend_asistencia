<?php

namespace App\Providers;

use App\Events\ResourceChanged;
use App\Events\UserCreated;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ActivateUserAfterVerified;
use App\Listeners\NotifyAdminsOnCrud;
use App\Listeners\NotifyGestorOnAttendanceOrRealClass;
use App\Listeners\SendEmailUserCreated;

/**
 * Proveedor de servicios de eventos.
 *
 * Registra manualmente los eventos y sus listeners correspondientes.
 * Define el mapa completo de comunicación por eventos de la aplicación.
 *
 * Eventos registrados:
 *
 * 1. Verified (Laravel nativo):
 *    - ActivateUserAfterVerified: activa el usuario al verificar email
 *
 * 2. UserCreated (custom):
 *    - SendEmailUserCreated: envía email de bienvenida (en cola)
 *
 * 3. ResourceChanged (custom):
 *    - NotifyAdminsOnCrud: notifica admins de cambios CRUD genéricos
 *    - NotifyGestorOnAttendanceOrRealClass: notifica gestores de inasistencias/clases
 *
 * Nota: El auto-discovery de eventos está deshabilitado (disableEventDiscovery)
 * para tener control manual y explícito de todos los eventos.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de eventos a sus listeners.
     *
     * Formato:
     * EventClass::class => [
     *     ListenerClass::class,
     *     AnotherListenerClass::class,
     * ]
     *
     * Un evento puede tener múltiples listeners.
     * Los listeners se ejecutan en el orden declarado.
     *
     * @var array<class-string, array<class-string>>
     */
    protected $listen = [
        /**
         * Evento nativo de Laravel: usuario verificó su email.
         * Disparado automáticamente al hacer click en enlace de verificación.
         */
        Verified::class => [
            ActivateUserAfterVerified::class, // Cambia status_id a 1 (activo)
        ],

        /**
         * Evento custom: nuevo usuario creado en el sistema.
         * Disparado manualmente en el controlador de usuarios.
         */
        UserCreated::class => [
            SendEmailUserCreated::class, // Envía email de bienvenida (ShouldQueue)
        ],

        /**
         * Evento custom: recurso modificado (crear/actualizar/eliminar).
         * Disparado en controladores CRUD después de cada operación.
         * Implementa ShouldDispatchAfterCommit para garantizar que
         * los datos ya están en BD antes de procesar.
         */
        ResourceChanged::class => [
            // Notifica a todos los administradores (excluye Attendance y RealClass)
            NotifyAdminsOnCrud::class,

            // Notifica al gestor de la ficha (solo Attendance y RealClass)
            NotifyGestorOnAttendanceOrRealClass::class,
        ],
    ];

    /**
     * Registra los eventos y desactiva el auto-discovery.
     *
     * disableEventDiscovery() previene que Laravel escanee
     * automáticamente los listeners, usando solo los declarados
     * en $listen para mayor control y rendimiento.
     */
    public function boot(): void
    {
        static::disableEventDiscovery();
    }

    /**
     * Sobrescribe la configuración de verificación de email.
     *
     * Método vacío para prevenir que el ServiceProvider base
     * registre rutas o comportamientos por defecto de verificación
     * que colisionarían con la implementación personalizada.
     */
    protected function configureEmailVerification(): void
    {}
}   