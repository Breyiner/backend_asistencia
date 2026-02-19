<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Extensión de permisos** (Permissions Enhancement).
 * 
 * Modifica: Tabla existente `permissions` (Spatie Laravel Permission)
 * Agrega: Campos de presentación y agrupación para UI/UX mejorada.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * **MODIFICA** la tabla `permissions` existente agregando:
     * - `display_name`: Nombre legible para mostrar en interfaces
     * - `group`: Categoría/grupo del permiso para organización
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name'); // Nombre para UI (ej. "Gestionar Usuarios")
            $table->string('group')->nullable();                      // Grupo/categoría (ej. "Usuarios", "Horarios")
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina los campos agregados de la tabla `permissions`.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'group']);
        });
    }
};
