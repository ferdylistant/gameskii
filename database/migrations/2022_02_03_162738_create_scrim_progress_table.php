<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrimProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrim_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scrims_id')->index();
            $table->enum('round',['1','2','3'])->nullable();
            $table->enum('result',['Win','Lose'])->nullable();
            $table->smallInteger('total_kills');
            $table->binary('screenshot')->nullable();
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
        Schema::dropIfExists('scrim_progress');
    }
}
