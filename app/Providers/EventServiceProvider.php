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

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Verified::class => [
            ActivateUserAfterVerified::class,
        ],
        UserCreated::class => [
            SendEmailUserCreated::class,
        ],
        ResourceChanged::class => [
            NotifyAdminsOnCrud::class,
            NotifyGestorOnAttendanceOrRealClass::class,
        ],
    ];

    public function boot(): void
    {
        static::disableEventDiscovery();
    }

    protected function configureEmailVerification(): void
    {}
}
