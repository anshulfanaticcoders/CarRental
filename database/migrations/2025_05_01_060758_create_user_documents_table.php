<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->string('driving_license_front')->nullable(); // URL for driving license front
            $table->string('driving_license_back')->nullable(); // URL for driving license back
            $table->string('passport_front')->nullable(); // URL for passport front
            $table->string('passport_back')->nullable(); // URL for passport back
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'verification_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_documents');
    }
};