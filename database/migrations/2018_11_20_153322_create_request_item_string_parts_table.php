<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemStringPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_item_string_parts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('item_request_id');
            $table->integer('string_index');
            $table->text('string_content');
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
        Schema::dropIfExists('request_item_string_parts');
    }
}
