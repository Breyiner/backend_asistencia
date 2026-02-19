<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Pivote Áreas-Usuarios** (AreaUser Pivot).
 * 
 * Tabla: `area_user` asigna usuarios a áreas específicas 
 * (muchos a muchos).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea tabla pivote `area_user` para relación muchos-a-muchos.
     */
    public function up(): void
    {
        Schema::create('area_user', function (Blueprint $table) {
            $table->id();                                         // ID Pivot (clave primaria)

            $table->unsignedBigInteger('area_id');                 // Área asignada
            $table->unsignedBigInteger('user_id');                 // Usuario asignado

            $table->foreign('area_id')->references('id')->on('areas'); // FK → areas.id
            $table->foreign('user_id')->references('id')->on('users');  // FK → users.id

            $table->unique(['area_id', 'user_id']);               // Unicidad área-usuario

            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_user');
    }
};
