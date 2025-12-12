<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('messages', 'booking_chat_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            // Add booking_chat_id foreign key
            $table->foreignId('booking_chat_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Enhanced message type
            $table->enum('message_type', ['text', 'emoji', 'image', 'video', 'audio', 'document', 'location', 'system'])->default('text')->after('message');

            // Message editing features
            $table->text('original_message')->nullable()->after('message');
            $table->timestamp('edited_at')->nullable()->after('read_at');

            // Message undo feature
            $table->boolean('is_undoing')->default(false)->after('edited_at');
            $table->timestamp('undo_deadline')->nullable()->after('is_undoing');

            // Enhanced metadata
            $table->json('message_metadata')->nullable()->after('undo_deadline'); // For additional message data

            // Relationship fields for new features
            $table->foreignId('chat_attachment_id')->nullable()->after('parent_id')->constrained()->onDelete('cascade');
            $table->foreignId('chat_location_id')->nullable()->after('chat_attachment_id')->constrained()->onDelete('cascade');

            // Indexes for performance
            $table->index(['booking_chat_id', 'created_at']);
            $table->index(['sender_id', 'message_type']);
            $table->index(['receiver_id', 'message_type']);
            $table->index('edited_at');
            $table->index('undo_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['booking_chat_id']);
            $table->dropForeign(['chat_attachment_id']);
            $table->dropForeign(['chat_location_id']);

            $table->dropColumn([
                'booking_chat_id',
                'message_type',
                'original_message',
                'edited_at',
                'is_undoing',
                'undo_deadline',
                'message_metadata',
                'chat_attachment_id',
                'chat_location_id'
            ]);
        });
    }
};
