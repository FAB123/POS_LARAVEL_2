<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseItemsTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_items_taxes', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id');
            $table->foreign('purchase_id')->references('purchase_id')->on('purchases');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->integer('line');
            $table->string('tax_name', 255);
            $table->double('percent', 15, 3);
            $table->double('amount', 15, 3);
            $table->primary(['purchase_id', 'item_id', 'line']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_items_taxes');
    }
}
