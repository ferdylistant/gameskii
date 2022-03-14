<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournamentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('eo_id')->index();
            $table->uuid('games_id')->index();
            $table->foreignId('ranks_id')->index();
            $table->string('name_tournament');
            $table->enum('tournament_system', [
                'Round 4',
                'Round 8',
                'Round 12',
                'Round 16',
                'Round 32',
                'Round 64',
            ]);
            $table->enum('bracket_type', ['Single Bracket', 'Double Bracket']);
            $table->dateTime('play_date')->nullable();
            $table->tinyInteger('quota');
            $table->integer('prize');
            $table->enum('result', ['On Going','Win','Lose']);
            $table->binary('sponsor_pict')->nullable();
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
        Schema::dropIfExists('tournaments');
    }
}
