<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Users** - Agrega FK ficha_id.
 * 
 * **ALTER TABLE:** `users` + columna ficha_id (nullable)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('ficha_id')->nullable()->after('id'); // FK ficha aprendices
            $table->foreign('ficha_id')->references('id')->on('fichas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['ficha_id']);   // Elimina FK primero
            $table->dropColumn('ficha_id');      // Elimina columna
        });
    }
};
