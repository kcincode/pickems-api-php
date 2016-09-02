<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNflPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfl_players', function (Blueprint $table) {
            $table->increments('id');
            $table->string('team_id', 5);
            $table->string('gsis_id', 15);
            $table->string('profile_id', 15);
            $table->string('name');
            $table->string('position', 5);
            $table->boolean('active');
            $table->timestamps();

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
        Schema::drop('nfl_players');
    }
}
