<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bmi_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bmi_id');
            $table->foreign('bmi_id')->references('id')->on('bmi_ids');
            $table->text('full_name');
            $table->text('dob');
            $table->biginteger('contact');
            $table->biginteger('contact2');
            $table->text('email');
            $table->unsignedBigInteger('country_of_residence');
            $table->foreign('country_of_residence')->references('id')->on('country_of_residences');
            $table->text('national_id_proof'); // array of national_id proof id
            $table->text('proof_details'); // object array {id,proof_no,expiry_date,image}
            $table->text('passport_no');
            $table->text('passport_expiry');
            $table->text('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bmi_users');
    }
};
