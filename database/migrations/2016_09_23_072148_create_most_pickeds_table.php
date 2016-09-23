<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMostPickedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('most_picked', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->integer('week')->unsigned();
            $table->string('name');
            $table->integer('number_picked')->unsigned();
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
        Schema::drop('most_picked');
    }
}
