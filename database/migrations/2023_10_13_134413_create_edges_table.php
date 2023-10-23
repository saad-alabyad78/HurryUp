<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdgesTable extends Migration
{
    public function up()
    {
        Schema::create('edges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_vertex_id');
            $table->unsignedBigInteger('target_vertex_id');
            $table->float('weight');
            $table->float('distance');
            $table->string('status');
            $table->float('time');
            $table->timestamps();

            $table->foreign('source_vertex_id')
                ->references('id')
                ->on('vertices')
                ->onDelete('cascade');

            $table->foreign('target_vertex_id')
                ->references('id')
                ->on('vertices')
                ->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edges');
    }
}
