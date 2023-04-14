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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('location');
            $table->text('investment_starting_date');
            $table->text('invested_amount')->nullable();
            $table->text('inactivated_date')->nullable();
            $table->integer('status');
            $table->text('investment_return')->nullable();
            $table->unsignedBigInteger('treasure_id');
            $table->foreign('treasure_id')->references('id')->on('bmi_ids');
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
        Schema::dropIfExists('companies');
    }
};
