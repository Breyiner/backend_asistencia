<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Cache** y **Locks** base de datos Laravel.
 * 
 * Tablas: `cache`, `cache_locks`
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Tabla **Cache** por base datos.
         */
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();    // Clave cache única
            $table->mediumText('value');         // Valor serializado
            $table->integer('expiration');       // Timestamp expiración
        });

        /**
         * Tabla **Cache Locks** para procesos concurrentes.
         */
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();    // Lock key única
            $table->string('owner');             // Propietario lock
            $table->integer('expiration');       // Timestamp expiración
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
