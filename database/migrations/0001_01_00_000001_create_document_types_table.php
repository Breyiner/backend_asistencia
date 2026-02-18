<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Tipos Documento** (CC, TI, CE, PA).
 * 
 * Tabla: `document_types`
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();                // ID autoincremental
            $table->string('name');      // "Cédula Ciudadanía", "Tarjeta Identidad"
            $table->string('acronym');   // "CC", "TI", "CE"
            $table->timestamps();        // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
