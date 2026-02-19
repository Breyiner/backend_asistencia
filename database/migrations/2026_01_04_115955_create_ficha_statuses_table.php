<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Estados Ficha** SENA.
 * 
 * Tabla: `ficha_statuses` (Activa, Finalizada, Suspendida)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ficha_statuses', function (Blueprint $table) {
            $table->id();                    // ID estado ficha
            $table->string('name');          // "Activa", "Finalizada"
            $table->string('description');   // Descripción estado
            $table->timestamps();            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha_statuses');
    }
};
