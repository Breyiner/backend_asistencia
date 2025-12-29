<?php

namespace App\Providers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ActivateUserAfterVerified;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Verified::class => [
            ActivateUserAfterVerified::class,
        ],
    ];
}