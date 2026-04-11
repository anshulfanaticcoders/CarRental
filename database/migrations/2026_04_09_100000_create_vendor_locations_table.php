<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_locations')) {
            return;
        }

        Schema::create('vendor_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('country_code', 2);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('location_type')->default('downtown');
            $table->string('iata_code', 3)->nullable();
            $table->string('phone')->nullable();
            $table->text('pickup_instructions')->nullable();
            $table->text('dropoff_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'is_active']);
            $table->index(['country_code', 'location_type']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_locations')) {
            Schema::dropIfExists('vendor_locations');
        }
    }
};
