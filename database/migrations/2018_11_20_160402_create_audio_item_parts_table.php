<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAudioItemPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audio_item_parts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('request_item_id');
            $table->integer('item_index');
            $table->string('voice');
            $table->string('audio_file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audio_item_parts');
    }
}
