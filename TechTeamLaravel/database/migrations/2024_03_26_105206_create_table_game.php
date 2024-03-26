<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('Game', function (Blueprint $table) {
            $table->string('game_id')->primary();
            $table->string('game_name');
            $table->timestamp('last_update')->nullable();
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('Game');

    }
};
