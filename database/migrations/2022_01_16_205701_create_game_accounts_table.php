<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_accounts', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('id_game_account',30)->unique();
            $table->string('nickname',20);
            $table->foreignId('games_id')->index();
            $table->foreignId('users_id')->index();
            $table->foreignId('ranks_id')->index();
            $table->integer('rank_Point')->nullable();
            $table->tinyInteger('is_online');
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
        Schema::dropIfExists('game_accounts');
    }
}
