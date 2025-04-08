<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('avatar')->nullable();
            $table->string('address_line1')->nullable();;
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();;
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code')->nullable();;
            $table->date('date_of_birth')->nullable();;
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('about')->nullable();
            $table->string('tax_identification')->nullable();
            $table->string('languages')->nullable();
            $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
}
