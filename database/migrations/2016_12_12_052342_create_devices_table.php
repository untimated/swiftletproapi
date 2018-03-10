<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('serial');
            $table->string('ip');
            $table->integer('bridge_id')->unsigned();
            $table->foreign('bridge_id')->references('id')->on('bridges')->onDelete('cascade');
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
        //
        Schema::dropIfExists('devices');
    }
}
