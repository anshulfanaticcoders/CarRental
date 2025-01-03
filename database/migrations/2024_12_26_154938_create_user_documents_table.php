<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key
            $table->enum('document_type', ['id_proof', 'address_proof', 'driving_license']);
            $table->string('document_number')->unique();
            $table->string('document_file');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps(); 
            $table->index(['user_id', 'document_type', 'verification_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_documents'); // Rollback action
    }
};
