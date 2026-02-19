<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Ambientes de formación** (Classrooms).
 * 
 * Tabla: `classrooms` almacena los espacios físicos o virtuales 
 * donde se imparten las clases o sesiones académicas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `classrooms` con el nombre y una descripción opcional.
     */
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();                                         // ID Classroom (clave primaria)
            $table->string('name', 80);                           // Nombre del ambiente (ej. "Laboratorio 301", "Aula Virtual 2")
            $table->string('description')->nullable();            // Descripción adicional u observaciones
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `classrooms` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
