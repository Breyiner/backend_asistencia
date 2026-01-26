<?php

namespace App\Providers;

use App\Models\RealClass;
use App\Policies\RealClassPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RealClass::class => RealClassPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}