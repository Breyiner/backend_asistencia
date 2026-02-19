<?php

namespace App\Providers;

use App\Models\RealClass;
use App\Policies\RealClassPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Proveedor de servicios de autorización.
 *
 * Registra las policies de la aplicación que controlan
 * qué usuarios pueden realizar qué acciones sobre cada modelo.
 *
 * Las policies se usan en controladores con:
 * - $this->authorize('create', $model)
 * - Gate::allows('create', $model)
 *
 * O en Blade con:
 * - @can('create', $model)
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapa de modelos a sus policies de autorización.
     *
     * Formato: ModelClass::class => PolicyClass::class
     *
     * Laravel automáticamente asocia cada modelo con su policy
     * cuando se llama a authorize() o Gate::allows().
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // RealClass usa RealClassPolicy para controlar
        // quién puede crear/editar clases reales
        RealClass::class => RealClassPolicy::class,
    ];

    /**
     * Registra las policies y gates de la aplicación.
     *
     * registerPolicies() recorre $policies y registra cada
     * par modelo-policy en el Gate de Laravel.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}