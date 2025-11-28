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
        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('thumbnail_path')->nullable();
            $table->json('metadata')->nullable(); // For dimensions, duration, etc.
            $table->enum('status', ['uploading', 'processing', 'completed', 'failed'])->default('uploading');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['booking_chat_id', 'status']);
            $table->index('sender_id');
            $table->index('file_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_attachments');
    }
};
