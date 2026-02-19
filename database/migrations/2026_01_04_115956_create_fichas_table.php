<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Fichas SENA**.
 * 
 * Tabla: `fichas` grupos de aprendices
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();                            // ID ficha
            $table->unsignedBigInteger('gestor_id'); // Coordinador/gestor
            $table->string('ficha_number')->unique(); // "2431021"
            $table->date('start_date');              // Fecha inicio
            $table->date('end_date');                // Fecha fin
            $table->unsignedBigInteger('training_program_id'); // Programa formación
            $table->unsignedBigInteger('status_id'); // Estado ficha
            $table->foreign('gestor_id')->references('id')->on('users');
            $table->foreign('training_program_id')->references('id')->on('training_programs');
            $table->foreign('status_id')->references('id')->on('ficha_statuses');
            $table->timestamps();                    // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichas');
    }
};
