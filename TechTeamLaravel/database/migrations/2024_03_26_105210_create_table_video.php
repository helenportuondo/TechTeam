<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('Video', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('user_name');
            $table->string('title');
            $table->string('created_at');
            $table->integer('view_count');
            $table->string('duration');
            $table->string('game_id');
            $table->foreign('game_id')->references('game_id')->on('Game');

        });
    }

    public function down()
    {
        Schema::dropIfExists('Video');
    }
};
