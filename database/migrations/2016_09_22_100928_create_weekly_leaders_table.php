<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeeklyLeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_leaders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('week')->unsigned();
            $table->integer('team_id')->unsigned()->references('id')->on('teams')->onDelete('cascade');
            $table->string('team');
            $table->integer('points')->unsigned();
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
        Schema::drop('weekly_leaders');
    }
}
