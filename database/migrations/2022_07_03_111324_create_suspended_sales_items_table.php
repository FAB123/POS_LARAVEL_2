<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspendedSalesItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suspended_sales_items', function (Blueprint $table) {
            $table->bigIncrements('track_id');
            $table->unsignedBigInteger('suspended_id');
            $table->foreign('suspended_id')->references('suspended_id')->on('suspended_sales');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->string('description', 255)->nullable();
            $table->string('serialnumber', 30)->nullable();
            $table->double('suspended_quantity', 15, 3)->nullable();
            $table->double('item_cost_price', 15, 3)->nullable();
            $table->double('discount', 15, 3)->default(0.000)->nullable();
            $table->enum('discount_type', ['C', 'P'])->nullable();
            $table->double('item_unit_price', 15, 3)->nullable();
            $table->double('item_sub_total', 15, 3)->nullable();
            $table->unsignedBigInteger('location_id')->index();
            $table->foreign('location_id')->references('location_id')->on('stock_locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suspended_sales_items');
    }
}
