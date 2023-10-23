<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVerticesTable extends Migration
{
    public function up()
    {
        Schema::create('vertices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_line_id');
            $table->string('name');

            $table->point('point');
            $table->boolean('is_busy')->default(false);
            $table->timestamp('busy_at')->nullable();
            $table->integer('feedback_count')->default(0);
                    $table->timestamps();

            $table->foreign('bus_line_id')
                ->references('id')
                ->on('bus_lines')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vertices');
    }
}
