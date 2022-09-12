<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id');
            $table->foreign('purchase_id')->references('purchase_id')->on('purchases');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->string('description', 255)->nullable();
            $table->string('serialnumber', 30)->nullable();
            $table->double('purchase_quantity', 15, 3)->nullable();
            $table->double('item_cost_price', 15, 3)->nullable();
            $table->unsignedBigInteger('location_id')->index();
            $table->foreign('location_id')->references('location_id')->on('stock_locations');
            $table->primary(['purchase_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_items');
    }
}
