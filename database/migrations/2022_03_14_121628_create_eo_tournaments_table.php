<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEoTournamentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournament_eos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('game_accounts_id', 30)->index();
            $table->string('organization_name',50);
            $table->string('organization_email',50)->unique();
            $table->string('organization_phone',20);
            $table->string('provinsi',30);
            $table->string('kabupaten',30);
            $table->string('kecamatan',30);
            $table->string('address',100);
            $table->tinyInteger('status');  // 0 = not active, 1 = active
            $table->dateTime('verified_at')->nullable();
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
        Schema::dropIfExists('tournament_eos');
    }
}
