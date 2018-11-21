<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestItemLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_item_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('request_item_id');
            $table->string('type');//->default(\App\Models\RequestItemLog::TYPE_INFO);
            $table->string('message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_item_logs');
    }
}
