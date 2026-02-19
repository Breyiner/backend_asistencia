<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Extensión de roles** (Roles Enhancement).
 * 
 * Modifica: Tabla existente `roles` (Spatie Laravel Permission)
 * Agrega: `code` para clave única legible y organización.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * **MODIFICA** la tabla `roles` existente agregando campo `code`.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');    // Código legible (ej. "INSTRUCTOR")
            $table->unique(['code', 'guard_name']);               // Unicidad código + guard
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['code', 'guard_name']);
            $table->dropColumn('code');
        });
    }
};
