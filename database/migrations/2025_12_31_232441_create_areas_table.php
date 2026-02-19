<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Áreas** de formación.
 * 
 * Tabla: `areas` (Informática, Mecánica, etc)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();                        // ID autoincremental
            $table->string('name')->unique();    // "Informática", "Mecánica"
            $table->string('description')->nullable(); // Descripción área
            $table->timestamps();                // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
