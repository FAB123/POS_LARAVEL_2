<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasePayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->index();
            $table->foreign('purchase_id')->references('purchase_id')->on('purchases');
            $table->unsignedBigInteger('payment_id')->index();
            $table->foreign('payment_id')->references('payment_id')->on('payment_options');
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
        Schema::dropIfExists('purchase_payments');
    }
}
