<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_follows', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignId('game_accounts_id')->index();
            $table->foreignId('acc_following_id')->index()->nullable();
            $table->foreignId('acc_followers_id')->index()->nullable();
            $table->enum('status_follow', ['request','confirm','reject']);
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
        Schema::dropIfExists('social_follows');
    }
}
