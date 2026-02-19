<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Personal Access Tokens** (Sanctum API).
 * 
 * Tabla: `personal_access_tokens`
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Tabla **Tokens API** Sanctum.
         * Soporta **polimorfismo** tokenable (User, etc).
         */
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();                            // ID token
            $table->morphs('tokenable');             // tokenable_id, tokenable_type
            $table->text('name');                    // "App Mobile", "API Client"
            $table->string('token', 64)->unique();   // Token hash único
            $table->text('abilities')->nullable();   // Permisos token
            $table->timestamp('last_used_at')->nullable(); // Último uso
            $table->timestamp('expires_at')->nullable()->index(); // Expiración
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
