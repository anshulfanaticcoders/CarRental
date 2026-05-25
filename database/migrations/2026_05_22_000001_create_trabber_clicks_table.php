<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabber_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('clickid');
            $table->string('offer_id')->nullable();
            $table->string('source')->default('trabber');
            $table->text('clicked_url')->nullable();
            $table->text('landing_url')->nullable();
            $table->json('search_metadata')->nullable();
            $table->timestamp('clicked_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('clickid');
            $table->index('offer_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabber_clicks');
    }
};
