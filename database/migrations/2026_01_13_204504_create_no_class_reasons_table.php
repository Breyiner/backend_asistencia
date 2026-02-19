<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Motivos de no clase** (NoClassReasons).
 * 
 * Tabla: `no_class_reasons` define las causas justificadas 
 * para la ausencia de clases (festivos, fallas técnicas, etc.).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `no_class_reasons` con nombre y descripción.
     */
    public function up(): void
    {
        Schema::create('no_class_reasons', function (Blueprint $table) {
            $table->id();                                         // ID Reason (clave primaria)
            $table->string('name');                               // Nombre del motivo (ej. "Festivo Nacional")
            $table->text('description')->nullable();              // Descripción detallada opcional
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('no_class_reasons');
    }
};
