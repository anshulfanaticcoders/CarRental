<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('expo_push_token', 191)->nullable()->after('phone');
            $table->string('expo_push_platform', 16)->nullable()->after('expo_push_token');
            $table->timestamp('expo_push_registered_at')->nullable()->after('expo_push_platform');
            $table->index('expo_push_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['expo_push_token']);
            $table->dropColumn(['expo_push_token', 'expo_push_platform', 'expo_push_registered_at']);
        });
    }
};
