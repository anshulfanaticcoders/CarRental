<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')
            ->where('status', 'draft')
            ->where('template', 'default')
            ->update([
                'status' => 'published',
            ]);
    }

    public function down(): void
    {
        // No-op: we can't know which were originally draft
    }
};
