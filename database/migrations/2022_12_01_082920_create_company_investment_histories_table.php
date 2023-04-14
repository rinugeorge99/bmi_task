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
        Schema::create('company_investment_histories', function (
            Blueprint $table
        ) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies');
            $table->text('invested_amount')->nullable();
            $table->text('date')->nullable();
            $table->text('investment_return')->nullable();
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
        Schema::dropIfExists('company_investment_histories');
    }
};
