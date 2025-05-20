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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('disk');
            $table->string('directory')->nullable(); // e.g., 'my_media'
            $table->string('filename');
            $table->string('extension');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size'); // Size in bytes
            $table->text('url')->nullable(); // Publicly accessible URL, could be long
            $table->string('title')->nullable(); // Optional title/alt text
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
