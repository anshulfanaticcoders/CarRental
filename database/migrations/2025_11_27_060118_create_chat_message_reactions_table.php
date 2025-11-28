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
        Schema::create('chat_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->constrained()->onDelete('cascade'); // This will reference existing messages table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji');
            $table->text('emoji_unicode')->nullable(); // Store unicode representation
            $table->timestamps();

            // Ensure one user can only react once per message with same emoji
            $table->unique(['message_id', 'user_id', 'emoji']);

            // Indexes for performance
            $table->index(['message_id', 'emoji']);
            $table->index(['booking_chat_id', 'emoji']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_reactions');
    }
};
