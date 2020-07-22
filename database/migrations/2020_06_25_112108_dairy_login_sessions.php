<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DairyLoginSessions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diary_login_sessions', function (Blueprint $table) {
            $table->id();
			$table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('diary_users')->onDelete('cascade');
            $table->string('device_type');
			$table->string('device_token');
            $table->string('header_api_token');
            $table->string('api_token');
			$table->tinyInteger('token_status');
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
        Schema::dropIfExists('diary_login_sessions');
    }
}
