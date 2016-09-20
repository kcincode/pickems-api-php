<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNflStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfl_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('week');
            $table->string('player_id', 15)->nullable();
            $table->string('team_id', 5)->nullable();
            $table->integer('td')->default(0);
            $table->integer('fg')->default(0);
            $table->integer('two')->default(0);
            $table->integer('xp')->default(0);
            $table->integer('diff')->default(0);
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('nfl_players')->onDelete('cascade');
            $table->foreign('team_id')->references('abbr')->on('nfl_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('nfl_stats');
    }
}
