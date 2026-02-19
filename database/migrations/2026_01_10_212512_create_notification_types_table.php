<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Tipos de notificación** (NotificationTypes).
 * 
 * Tabla: `notification_types` define los tipos de notificaciones 
 * del sistema (ej. asistencia, calificaciones, recordatorios de clase).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `notification_types` con nombre y clave única.
     */
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();                                         // ID NotificationType (clave primaria)
            $table->string('name');                               // Nombre descriptivo (ej. "Asistencia Pendiente")
            $table->string('key')->unique();                      // Clave única para lógica (ej. "attendance_pending")
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `notification_types` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
