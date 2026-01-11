<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('notification_type_id');
            $table->foreign('notification_type_id')
                ->references('id')
                ->on('notification_types');

            $table->string('title');
            $table->text('content');

            $table->morphs('modelable');

            $table->timestamps();
        });

        Schema::create('notification_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('notification_id');
            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->unique(['notification_id', 'user_id'], 'uniq_notification_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_user');
        Schema::dropIfExists('notifications');
    }
};
