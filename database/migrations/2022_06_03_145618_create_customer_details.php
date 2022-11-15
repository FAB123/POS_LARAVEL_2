<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_details', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->string('address_line_1', 255)->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('city_sub_division', 10)->nullable();
            $table->string('street', 20)->nullable();
            $table->string('additional_street', 15)->nullable();
            $table->string('building_number', 6)->nullable();
            $table->string('plot_identification', 6)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 6)->nullable();
            $table->string('comments', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_details');
    }
}
