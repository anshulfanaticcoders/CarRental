<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            $table->string('driving_license_front')->nullable()->after('driving_license');
            $table->string('driving_license_back')->nullable()->after('driving_license_front');
        });
    }

    public function down()
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            $table->dropColumn(['driving_license_front', 'driving_license_back']);
        });
    }
};
