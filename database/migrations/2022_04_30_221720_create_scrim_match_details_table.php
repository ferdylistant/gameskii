<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrimMatchDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrim_match_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('scrims_id')->index();
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
        Schema::dropIfExists('scrim_match_details');
    }
}
