<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Definición del scheduler de tareas programadas.
 *
 * En Laravel 11+, las tareas se definen directamente en este archivo
 * en lugar de en app/Console/Kernel.php.
 *
 * Para ejecutar el scheduler en producción, agregar al cron del servidor:
 * * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
 *
 * Para ejecutar en desarrollo:
 * php artisan schedule:work
 */

/**
 * Tarea: Notificar al gestor cuando un aprendiz cumple 18 años.
 *
 * Comando: apprentices:notify-adults
 * Frecuencia: diariamente a las 07:00
 * withoutOverlapping: evita que se ejecute si la ejecución anterior
 * aún no ha terminado (previene duplicados).
 *
 * Implementado en: App\Console\Commands\NotifyGestorAdultApprentices
 */
Schedule::command('apprentices:notify-adults')
    ->dailyAt('07:00')      // Se ejecuta todos los días a las 7:00 AM
    ->withoutOverlapping(); // No ejecuta si ya hay una instancia corriendo