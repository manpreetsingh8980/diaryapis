<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiaryRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_rates', function (Blueprint $table) {
            $table->increments('id');
			$table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('diary_users')->onDelete('cascade');
			$table->bigInteger('fat_id')->unsigned();
            $table->foreign('fat_id')->references('id')->on('diary_fat')->onDelete('cascade');
			$table->bigInteger('snf_id')->unsigned();
            $table->foreign('snf_id')->references('id')->on('diary_snf')->onDelete('cascade');
            $table->string('rate');
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
        Schema::dropIfExists('diary_rates');
    }
}
