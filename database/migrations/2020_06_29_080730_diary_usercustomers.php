<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiaryUsercustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_usercustomers', function (Blueprint $table) {
            $table->increments('id');
			$table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('diary_users')->onDelete('cascade');
            $table->string('name');
			$table->string('phone_number');
            $table->string('address');
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
        Schema::dropIfExists('diary_usercustomers');
    }
}
