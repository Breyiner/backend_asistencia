<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Remember Token en usuarios** (Users RememberToken).
 * 
 * Modifica: Tabla existente `users`
 * Agrega: Soporte para "Remember Me" en autenticación Laravel.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * **MODIFICA** tabla `users` agregando campo estándar de Laravel Auth.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken()->after('password');           // Token para "Remember Me" (estándar Laravel)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
