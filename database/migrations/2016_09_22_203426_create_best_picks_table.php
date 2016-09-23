<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBestPicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('best_picks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->integer('week')->unsigned();
            $table->string('pick1');
            $table->integer('pick1_points')->unsigned();
            $table->boolean('pick1_playmaker')->default(false);
            $table->string('pick2');
            $table->integer('pick2_points')->unsigned();
            $table->boolean('pick2_playmaker')->default(false);
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
        Schema::drop('best_picks');
    }
}
