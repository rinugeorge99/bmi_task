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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bmi_id');
            $table->foreign('bmi_id')->references('id')->on('bmi_ids');
            $table->text('resi_city');
            $table->text('street_name');
            $table->text('resi_house_name');
            $table->text('resi_landmark')->nullable();
            $table->text('po_box')->nullable();
            $table->text('house_name');
            $table->text('post');
            $table->text('district');
            $table->text('state');
            $table->text('pincode');
            $table->text('landmark')->nullable();
            $table->text('contact');
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
        Schema::dropIfExists('user_addresses');
    }
};
