<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Trimestres** académicos.
 * 
 * Tabla: `terms` (Trimestre 1, 2, 3, 4 ...)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();                    // ID trimestre
            $table->string('name');          // "Trimestre 1", "Trimestre 2"
            $table->timestamps();            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
