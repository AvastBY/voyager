<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThumbsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thumbs', function (Blueprint $table) {
            $table->id();
            $table->string('mark')->unique();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->boolean('cover')->nullable();
            $table->boolean('fix_canvas')->nullable();
            $table->boolean('upsize')->nullable();
            $table->integer('quality')->nullable()->default(90);
            $table->integer('blur')->nullable()->default(0);
            $table->string('canvas_color')->nullable();
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
        Schema::dropIfExists('thumbs');
    }
}
