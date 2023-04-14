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
        Schema::create('user_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bmi_id');
            $table->foreign('bmi_id')->references('id')->on('bmi_ids');
            $table->text('name');
            $table->text('relation');
            $table->text('address');
            $table->text('contact');
            $table->unsignedBigInteger('national_id_proof');
            $table->foreign('national_id_proof')->references('id')->on('national_id_proofs');
            $table->text('document');
            $table->text('passport_no');
            $table->text('passport_expiry');
            $table->text('passport_upload');
            $table->text('dob');
            $table->text('acc_no');
            $table->text('ifsc');
            $table->text('branch');
            $table->text('bank_name');
            $table->text('beneficiary_share');
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
        Schema::dropIfExists('user_beneficiaries');
    }
};
