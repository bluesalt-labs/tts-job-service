<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAudioItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audio_items', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('request_item_id');
            $table->string('voice');
            $table->string('status')->default(''); // todo

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audio_items');
    }
}
