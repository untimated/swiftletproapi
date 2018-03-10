<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicedatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devicedatas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('humidity');
            $table->integer('temperature');
            $table->integer('device_id')->unsigned();
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
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
        Schema::dropIfExists('devicedatas');
    }
}
