<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Allow null logable for events that aren't tied to a single model
        // (login, logout, bulk_deleted, login_failed, password_reset).
        DB::statement('ALTER TABLE activity_logs MODIFY logable_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE activity_logs MODIFY logable_type VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Cannot safely revert without dropping null rows; leave nullable.
    }
};
