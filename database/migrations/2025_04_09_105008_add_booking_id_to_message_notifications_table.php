<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('message_notifications', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade')->after('message');
        });
    }

    public function down()
    {
        Schema::table('message_notifications', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
};