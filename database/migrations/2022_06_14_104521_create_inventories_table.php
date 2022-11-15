<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->bigIncrements('trans_id');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->unsignedBigInteger('trans_user');
            $table->foreign('trans_user')->references('employee_id')->on('employees');
            $table->string('trans_comment', 255)->nullable();
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('location_id')->on('stock_locations');
            $table->double('quantity', 15, 3);
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
        Schema::dropIfExists('inventories');
    }
}
