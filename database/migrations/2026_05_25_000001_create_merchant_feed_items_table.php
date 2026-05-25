<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_feed_items', function (Blueprint $table) {
            $table->id();
            $table->string('feed_name', 50);
            $table->string('feed_key', 100);
            $table->string('source', 20);
            $table->string('provider', 80)->nullable();
            $table->string('provider_vehicle_id', 191)->nullable();
            $table->string('title', 500);
            $table->text('description');
            $table->text('link');
            $table->text('image_link');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3);
            $table->string('availability', 20);
            $table->string('brand', 120)->nullable();
            $table->string('product_type', 191)->nullable();
            $table->string('condition', 20)->default('used');
            $table->string('location_name', 191)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->json('raw_attributes')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['feed_name', 'feed_key'], 'merchant_feed_items_feed_key_unique');
            $table->index(['feed_name', 'source', 'availability'], 'merchant_feed_items_feed_source_avail_idx');
            $table->index(['feed_name', 'expires_at'], 'merchant_feed_items_feed_expires_idx');
            $table->index(['provider', 'provider_vehicle_id'], 'merchant_feed_items_provider_vehicle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_feed_items');
    }
};
