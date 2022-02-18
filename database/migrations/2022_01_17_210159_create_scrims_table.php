<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranks_id')->index();
            $table->foreignId('game_accounts_id')->index();
            $table->string('name_party');
            $table->binary('image');
            $table->tinyInteger('quota');
            $table->tinyInteger('round');
            $table->dateTime('play_date')->nullable();
            $table->enum('status', ['On','Off']);
            $table->enum('result', ['Win','Lose','On Going']);
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
        Schema::dropIfExists('scrims');
    }
}
