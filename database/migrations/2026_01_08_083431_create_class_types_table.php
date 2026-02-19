<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Tipos de clase** (ClassTypes).
 * 
 * Tabla: `class_types` define las categorías o modalidades de sesión 
 * (por ejemplo: teórica, práctica, virtual, presencial).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `class_types` con nombre y descripción opcional.
     */
    public function up(): void
    {
        Schema::create('class_types', function (Blueprint $table) {
            $table->id();                                         // ID ClassType (clave primaria)
            $table->string('name', 50);                           // Nombre del tipo de clase (ej. "Teórica", "Práctica")
            $table->text('description')->nullable();              // Descripción detallada u observaciones
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `class_types` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_types');
    }
};
