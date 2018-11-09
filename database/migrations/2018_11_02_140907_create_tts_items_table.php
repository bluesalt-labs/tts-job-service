<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTtsItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tts_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('unique_id');
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('user_id')->nullable();
            $table->string('status')->default(\App\Models\TTSItem::STATUS_DEFAULT);
            $table->string('text_file');
            $table->string('audio_file');
            $table->string('voice_id');
            $table->string('output_format')->default(\App\Helpers\TextToSpeech::OUTPUT_FORMAT_DEFAULT);
            $table->string('status_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tts_items');
    }
}
