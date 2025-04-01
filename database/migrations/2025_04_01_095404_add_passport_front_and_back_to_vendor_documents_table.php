<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassportFrontAndBackToVendorDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            // Add passport_front and passport_back columns after passport
            $table->string('passport_front')->nullable()->after('passport');
            $table->string('passport_back')->nullable()->after('passport_front');
        });
    }

    public function down()
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            // Drop the added columns if rolling back
            $table->dropColumn('passport_front');
            $table->dropColumn('passport_back');
        });
    }
}