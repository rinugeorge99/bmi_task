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
        Schema::create('company_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->text('amount');
            $table->unsignedBigInteger('amount_cat_id');
            $table->foreign('amount_cat_id')->references('id')->on('amount_categories');
            $table->text('date');
            $table->unsignedBigInteger('fund_collector_id');
            $table->foreign('fund_collector_id')->references('id')->on('bmi_ids');
            $table->boolean('transfer');
            $table->text('transfer_date')->nullable();
            $table->boolean('transfer_verification');
            $table->text('transfer_verification_date')->nullable();
            $table->boolean('transferToBank');
            $table->text('transferToBank_date')->nullable();
            $table->text('remarks')->nullable();
            $table->text('collected_from')->nullable();
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
        Schema::dropIfExists('company_transactions');
    }
};
