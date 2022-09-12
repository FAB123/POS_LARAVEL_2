<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotationsItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotations_items', function (Blueprint $table) {
            $table->bigIncrements('track_id');
            $table->unsignedBigInteger('quotation_id');
            $table->foreign('quotation_id')->references('quotation_id')->on('quotations');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->string('description', 255)->nullable();
            $table->string('serialnumber', 30)->nullable();
            $table->double('quotation_quantity', 15, 3)->nullable();
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
        Schema::dropIfExists('qutations_items');
    }
}
