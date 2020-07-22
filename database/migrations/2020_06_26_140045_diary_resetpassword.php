<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiaryResetpassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_resetpassword', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
			$table->string('token');
			$table->string('created_at');
			$table->string('expires_at');
			$table->string('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diary_resetpassword');
    }
}
