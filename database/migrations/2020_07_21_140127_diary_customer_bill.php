<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiaryCustomerBill extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_customer_bill', function (Blueprint $table) {
            $table->increments('id');
			$table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('diary_users')->onDelete('cascade');
			$table->Integer('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('diary_usercustomers')->onDelete('cascade');
            $table->string('total_weight')->nullable();
			$table->string('total_amount')->nullable();
			$table->string('start_date')->nullable();
			$table->string('end_date')->nullable();
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
        Schema::dropIfExists('diary_customer_bill');
    }
}
