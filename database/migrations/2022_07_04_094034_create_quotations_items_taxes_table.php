<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotationsItemsTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotations_items_taxes', function (Blueprint $table) {
            $table->bigIncrements('track_id');
            $table->unsignedBigInteger('quotation_id');
            $table->foreign('quotation_id')->references('quotation_id')->on('quotations');
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
        Schema::dropIfExists('qutations_items_taxes');
    }
}
