<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_sip_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bmi_id');
            $table
                ->foreign('bmi_id')
                ->references('id')
                ->on('bmi_ids');
            $table->text('year');
            $table->text('month');
            $table->unsignedBigInteger('monthly_sip_id');
            $table
                ->foreign('monthly_sip_id')
                ->references('id')
                ->on('monthly_sips');
            $table->unsignedBigInteger('transaction_id');
            $table
                ->foreign('transaction_id')
                ->references('id')
                ->on('user_transactions');
            $table->integer('status');
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
        Schema::dropIfExists('monthly_sip_details');
    }
};
