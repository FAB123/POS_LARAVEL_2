<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspendedSalesItemsTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suspended_sales_items_taxes', function (Blueprint $table) {
            $table->bigIncrements('track_id');
            $table->unsignedBigInteger('suspended_id');
            $table->foreign('suspended_id')->references('suspended_id')->on('suspended_sales');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->integer('line');
            $table->string('tax_name', 255);
            $table->double('percent', 15, 3);
            $table->double('amount', 15, 3);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suspended_sales_items_taxes');
    }
}
