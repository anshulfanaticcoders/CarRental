<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->enum('status', ['new', 'read', 'responded'])->default('new');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_submissions');
    }
};