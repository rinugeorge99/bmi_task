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
        Schema::create('user_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bmi_id');
            $table->foreign('bmi_id')->references('id')->on('bmi_ids');
            $table->boolean('abroad');
            $table->text('bank_name');
            $table->text('acc_name');
            $table->text('acc_no');
            $table->text('ifsc_code')->nullable();
            $table->text('branch')->nullable();
            $table->text('currency');
            $table->text('iban_no')->nullable();
            $table->text('swift')->nullable();
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
        Schema::dropIfExists('user_banks');
    }
};
