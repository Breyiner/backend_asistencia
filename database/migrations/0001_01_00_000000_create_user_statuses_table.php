<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Estados Usuario** (Activo, Inactivo, Suspendido).
 * 
 * Tabla: `user_statuses`
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_statuses', function (Blueprint $table) {
            $table->id();                    // ID autoincremental
            $table->string('name');          // "Activo", "Inactivo"
            $table->string('description');   // Descripción estado
            $table->timestamps();            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statuses');
    }
};
