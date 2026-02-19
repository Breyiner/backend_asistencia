<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Perfiles Usuario**.
 * 
 * Tabla: `profiles` 1:1 con users
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();                                    // ID perfil
            $table->unsignedBigInteger('user_id')->unique(); // 1:1 con users
            $table->foreign('user_id')->references('id')->on('users'); // FK
            $table->string('first_name');                    // Primer nombre
            $table->string('last_name');                     // Apellido
            $table->string('telephone_number')->nullable();  // Teléfono opcional
            $table->date('birth_date')->nullable();          // Fecha nacimiento opcional
            $table->timestamps();                            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
