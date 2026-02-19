<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Horarios** (Schedules).
 * 
 * Tabla: `schedules` almacena las planificaciones o 
 * configuraciones de horario asociadas a una Ficha + Trimestre.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `schedules`, vinculada a `ficha_terms` mediante clave foránea.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();                                       // ID Schedule (clave primaria)
            $table->string('description')->nullable();           // Descripción del horario (opcional)
            $table->unsignedBigInteger('ficha_term_id');         // Relación con FichaTerm (Ficha + Trimestre)
            $table->foreign('ficha_term_id')
                  ->references('id')
                  ->on('ficha_terms');                           // FK → ficha_terms.id
            $table->timestamps();                                // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `schedules` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
