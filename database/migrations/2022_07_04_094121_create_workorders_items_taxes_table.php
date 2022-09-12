<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkordersItemsTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workorders_items_taxes', function (Blueprint $table) {
            $table->unsignedBigInteger('workorder_id');
            $table->bigIncrements('track_id');
            $table->foreign('workorder_id')->references('workorder_id')->on('workorders');
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
        Schema::dropIfExists('workorders_items_taxes');
    }
}
