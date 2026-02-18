<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Queues/Jobs** sistema Laravel.
 * 
 * Tablas: `jobs`, `job_batches`, `failed_jobs`
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Tabla **Jobs/Cola** trabajos pendientes.
         */
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();           // Cola específica
            $table->longText('payload');                // Datos job serializados
            $table->unsignedTinyInteger('attempts');    // Intentos fallidos
            $table->unsignedInteger('reserved_at')->nullable(); // Reservado hasta
            $table->unsignedInteger('available_at');    // Disponible desde
            $table->unsignedInteger('created_at');      // Creado en
        });

        /**
         * Tabla **Job Batches** lotes de trabajos.
         */
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        /**
         * Tabla **Failed Jobs** trabajos fallidos.
         */
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
