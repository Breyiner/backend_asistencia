<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Días sin clase** (NoClassDays).
 * 
 * Tabla: `no_class_days` registra los días específicos sin clases 
 * por ficha, vinculados a un motivo justificado.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `no_class_days` vinculando ficha → fecha → motivo.
     */
    public function up(): void
    {
        Schema::create('no_class_days', function (Blueprint $table) {
            $table->id();                                         // ID NoClassDay (clave primaria)

            $table->unsignedBigInteger('ficha_id');                // Ficha afectada
            $table->unsignedBigInteger('reason_id');               // Motivo de no clase
            $table->date('date');                                 // Fecha del día sin clases
            $table->text('observations')->nullable();              // Observaciones adicionales

            $table->foreign('ficha_id')
                  ->references('id')
                  ->on('fichas');                                   // FK → fichas.id

            $table->foreign('reason_id')
                  ->references('id')
                  ->on('no_class_reasons');                         // FK → no_class_reasons.id

            $table->unique(['ficha_id', 'date']);                 // Un día sin clase por ficha

            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('no_class_days');
    }
};
