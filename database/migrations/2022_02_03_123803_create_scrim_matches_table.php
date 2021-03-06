<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrimMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrim_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scrims_id')->index();
            $table->uuid('teams_id')->index();
            $table->dateTime('play_date')->nullable();
            $table->enum('result',['Not yet','Ready','On Going','Win','Lose'])->nullable();
            $table->tinyInteger('score')->nullable();
            $table->enum('round',['Not yet','1','2','3'])->nullable();
            $table->tinyInteger('status_match')->nullable();
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
        Schema::dropIfExists('scrim_matches');
    }
}
