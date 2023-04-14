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
        Schema::create('bmi_ids', function (Blueprint $table) {
            $table->id();
            $table->text('bmi_id');
            $table->text('name');
            $table->text('email');
            // $table->biginteger('contact');
            $table->biginteger('contact')->unsigned();

            $table->text('password');
            $table->boolean('mail_status');
            $table->boolean('verify');
            $table->integer('status');
            $table->text('deactivated_by')->nullable();
            $table->text('userid');
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
        Schema::dropIfExists('bmi_ids');
    }
};
