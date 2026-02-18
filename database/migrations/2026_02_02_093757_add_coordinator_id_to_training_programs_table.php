<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Coordinador en programas** (TrainingPrograms Coordinator).
 * 
 * Modifica: Tabla existente `training_programs`
 * Agrega: Coordinador responsable opcional.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * **MODIFICA** tabla `training_programs` agregando coordinador.
     */
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('coordinator_id')->nullable()->after('area_id'); // Coordinador del programa
            $table->foreign('coordinator_id')->references('id')->on('users');          // FK → users.id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropForeign(['coordinator_id']);
            $table->dropColumn('coordinator_id');
        });
    }
};
