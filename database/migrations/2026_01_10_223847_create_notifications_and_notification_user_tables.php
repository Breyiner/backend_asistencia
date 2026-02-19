<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Sistema de notificaciones** (Notifications + Pivot).
 * 
 * Tablas: 
 * - `notifications`: Contenido de las notificaciones con polimorfismo
 * - `notification_user`: Tabla pivote para asignar notificaciones a usuarios por rol
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea las tablas `notifications` y `notification_user` para el sistema de notificaciones.
     */
    public function up(): void
    {
        // Tabla principal de notificaciones
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();                                         // ID Notification (clave primaria)

            $table->unsignedBigInteger('notification_type_id');    // Tipo de notificación (FK → notification_types)
            $table->foreign('notification_type_id')
                  ->references('id')
                  ->on('notification_types');                       // FK → notification_types.id

            $table->string('title');                              // Título de la notificación
            $table->text('content');                              // Contenido/mensaje completo

            $table->morphs('modelable');                          // Polimorfismo: vincula a cualquier modelo (real_class, ficha, etc.)

            $table->timestamps();                                 // created_at, updated_at
        });

        // Tabla pivote: asignación notificación → usuarios
        Schema::create('notification_user', function (Blueprint $table) {
            $table->id();                                         // ID Pivot (clave primaria)

            $table->unsignedBigInteger('notification_id');        // Notificación asignada
            $table->foreign('notification_id')
                  ->references('id')
                  ->on('notifications');                            // FK → notifications.id

            $table->unsignedBigInteger('user_id');                // Usuario destinatario
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');                                    // FK → users.id

            $table->timestamp('read_at')->nullable();             // Marca cuando se leyó la notificación

            $table->string('role_code');                          // Rol del usuario (ej. "INSTRUCTOR", "APRENDIZ")

            $table->timestamps();                                 // created_at, updated_at

            $table->unique(['notification_id', 'user_id'], 'uniq_notification_user'); // Unicidad notificación-usuario
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina las tablas en orden correcto (pivote primero).
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_user');
        Schema::dropIfExists('notifications');
    }
};
