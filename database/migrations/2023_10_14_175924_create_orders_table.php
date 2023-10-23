<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->json('user_ids')->nullable();
            $table->unsignedInteger('top_passenger_count')->default(1);
            $table->unsignedInteger('current_passenger_count')->default(1);
            $table->unsignedBigInteger('destination_vertices_id');
            $table->unsignedInteger('estimated_price')->nullable();
            $table->boolean('is_hurry')->default(false);
            $table->string('status')->default('pending');
            $table->enum('genders', ['Male', 'Female','both'])->nullable();

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
        Schema::dropIfExists('orders');
    }
}
