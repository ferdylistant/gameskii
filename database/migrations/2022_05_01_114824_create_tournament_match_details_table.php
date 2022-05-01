<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournamentMatchDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournament_match_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tournaments_id')->index();
            $table->uuid('teams1_id')->index()->nullable();
            $table->uuid('teams2_id')->index()->nullable();
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
        Schema::dropIfExists('tournament_match_details');
    }
}
