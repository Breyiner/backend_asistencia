<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('apprentices:notify-adults')
    ->dailyAt('07:00')
    ->withoutOverlapping();
