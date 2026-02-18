<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Roles/Permisos** - Agrega descripción.
 * 
 * **ALTER TABLE:** `roles`, `permissions` + columna description
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * **Roles Spatie** - Agrega descripción legible.
         */
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable(); // "Instructor del SENA"
        });

        /**
         * **Permisos Spatie** - Agrega descripción legible.
         */
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable(); // "Crear fichas SENA"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * Elimina columna description de roles.
         */
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        /**
         * Elimina columna description de permisos.
         */
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
