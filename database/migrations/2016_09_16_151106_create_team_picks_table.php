<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamPicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_picks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->unsigned()->references('id')->on('teams')->onDelete('cascade');
            $table->integer('week')->unsigned();
            $table->integer('number')->unsigned();
            $table->integer('nfl_stat_id')->unsigned()->references('id')->on('nfl_stats')->onDelete('cascade');
            $table->boolean('playmaker')->default(false);
            $table->boolean('valid')->default(true);
            $table->string('reason')->nullable();
            $table->datetime('picked_at');
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
        Schema::drop('team_picks');
    }
}
