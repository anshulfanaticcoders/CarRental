<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider', 40);
            $table->string('provider_location_id', 120);
            $table->string('raw_name', 255)->nullable();
            $table->string('raw_address', 255)->nullable();
            $table->string('raw_city', 120)->nullable();
            $table->string('raw_state', 120)->nullable();
            $table->string('raw_country', 120)->nullable();
            $table->decimal('raw_latitude', 10, 6)->nullable();
            $table->decimal('raw_longitude', 10, 6)->nullable();
            $table->char('raw_iata', 3)->nullable();
            $table->string('raw_type', 50)->nullable();

            $table->string('name_norm', 255)->nullable();
            $table->string('city_norm', 120)->nullable();
            $table->string('country_norm', 120)->nullable();
            $table->string('type_norm', 50)->nullable();
            $table->char('iata_norm', 3)->nullable();
            $table->string('geohash', 16)->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_location_id'], 'provider_location_unique');
            $table->index(['provider', 'country_norm', 'city_norm'], 'provider_location_country_city');
            $table->index(['provider', 'iata_norm'], 'provider_location_iata');
            $table->index('geohash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_locations');
    }
};
