<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostPermitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_permitions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('class_id');
            $table->integer('parents');
            $table->integer('staff');
            $table->timestamps();

            $table->foreign('post_id')
            ->references('id')
            ->on('psots')
            ->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_permitions');
    }
}
