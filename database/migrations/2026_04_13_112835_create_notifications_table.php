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
            $table->unsignedBigInteger('user_id')->nullable(); // Admin/recipient user
            $table->unsignedBigInteger('performed_by_id')->nullable(); // User who performed the action
            $table->string('performed_by_name')->nullable(); // Name of user who performed action
            $table->string('type'); // 'student', 'payment', 'user'
            $table->string('action'); // 'created', 'updated', 'deleted', 'status_changed'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Store event data (changes, affected records)
            $table->json('changes')->nullable(); // Store what changed (for updates)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('performed_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index(['performed_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
