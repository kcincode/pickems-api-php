<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamPlayoffPicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_playoff_picks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->unsigned();
            $table->string('qb1', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('qb2', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('rb1', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('rb2', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('rb3', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('wrte1', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('wrte2', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('wrte3', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('wrte4', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('wrte5', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('k1', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('k2', 15)->nullable()->references('gsis_id')->on('nfl_players')->onDelete('cascade');
            $table->string('playmakers', 25)->nullable();
            $table->boolean('valid')->default(true);
            $table->text('reason')->nullable();
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
        Schema::dropIfExists('team_playoff_picks');
    }
}
