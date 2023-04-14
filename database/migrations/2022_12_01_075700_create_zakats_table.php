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
        Schema::create('zakats', function (Blueprint $table) {
            $table->id();
            $table->text('date');
            $table->text('from_date');
            $table->text('to_date');
            $table->text('profit');
            $table->text('zakat');
            $table->text('zakat_details')->nullable();
            $table->boolean('transferFromBank');
            $table->text('transferFromBank_date')->nullable();
            $table->unsignedBigInteger('treasurer');
            $table->foreign('treasurer')
            ->references('id')
            ->on('bmi_ids');
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
        Schema::dropIfExists('zakats');
    }
};
