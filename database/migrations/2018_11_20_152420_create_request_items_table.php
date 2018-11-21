<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_items', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('unique_id');
            $table->string('user_id')->nullable();
            $table->string('status')->default(\App\Models\RequestItem::STATUS_DEFAULT); // todo;
            $table->string('output_name')->nullable();
            $table->string('text_file');
            $table->jsonb('voice_ids')->default('[]');
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
        Schema::dropIfExists('request_items');
    }
}
