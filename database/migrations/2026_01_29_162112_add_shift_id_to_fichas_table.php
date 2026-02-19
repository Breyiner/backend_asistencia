<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Jornada en fichas** (Fichas Shift Extension).
 * 
 * Modifica: Tabla existente `fichas`
 * Agrega: Relación con tabla `shifts` (mañana, tarde, noche).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * **MODIFICA** tabla `fichas` agregando turno opcional.
     */
    public function up(): void
    {
        Schema::table('fichas', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('gestor_id'); // Jornada (diurna/nocturna)
            $table->foreign('shift_id')
                  ->references('id')
                  ->on('shifts');                                   // FK → shifts.id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fichas', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
