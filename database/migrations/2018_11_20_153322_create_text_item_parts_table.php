<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextItemParts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('text_item_parts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('request_item_id');
            $table->integer('item_index');
            $table->text('item_content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('text_item_parts');
    }
}
