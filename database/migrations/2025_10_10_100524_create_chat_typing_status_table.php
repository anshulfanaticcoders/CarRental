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
        Schema::create('chat_typing_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->boolean('is_typing')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // Ensure a user can only have one typing status per booking
            $table->unique(['user_id', 'booking_id']);

            // Index for faster queries
            $table->index(['booking_id', 'is_typing']);
            $table->index(['last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_typing_status');
    }
};
