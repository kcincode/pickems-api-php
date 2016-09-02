<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNflGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfl_games', function (Blueprint $table) {
            $table->increments('id');
            $table->datetime('starts_at');
            $table->integer('week');
            $table->string('type');
            $table->string('eid')->unique();
            $table->string('gsis')->unique();
            $table->string('home_team_id', 5);
            $table->string('away_team_id', 5);
            $table->string('winning_team_id', 5)->nullable();
            $table->string('losing_team_id', 5)->nullable();
            $table->timestamps();

            $table->foreign('home_team_id')->references('abbr')->on('nfl_teams')->onDelete('cascade');
            $table->foreign('away_team_id')->references('abbr')->on('nfl_teams')->onDelete('cascade');
            $table->foreign('winning_team_id')->references('abbr')->on('nfl_teams')->onDelete('cascade');
            $table->foreign('losing_team_id')->references('abbr')->on('nfl_teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('nfl_games');
    }
}
