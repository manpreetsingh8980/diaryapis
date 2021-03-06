<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiaryMilkEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_milk_enteries', function (Blueprint $table) {
            $table->increments('id');
			$table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('diary_users')->onDelete('cascade');
			$table->Integer('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('diary_usercustomers')->onDelete('cascade');
            $table->string('weight')->nullable();
			$table->string('fat')->nullable();
			$table->string('snf')->nullable();
			$table->string('total_amount')->nullable();
			$table->string('type')->comment('1=>cow, 2=>buffelo');
			$table->string('time')->comment('1=>morning, 2=>evening');
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
        Schema::dropIfExists('diary_milk_enteries');
    }
}
