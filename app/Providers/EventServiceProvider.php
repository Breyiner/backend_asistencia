<?php

namespace App\Providers;

use App\Events\ResourceChanged;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ActivateUserAfterVerified;
use App\Listeners\NotifyAdminsOnCrud;
use App\Listeners\NotifyGestorOnAttendanceOrRealClass;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Verified::class => [
            ActivateUserAfterVerified::class,
        ],
        ResourceChanged::class => [
            NotifyAdminsOnCrud::class,
            NotifyGestorOnAttendanceOrRealClass::class,
        ],
    ];
}
