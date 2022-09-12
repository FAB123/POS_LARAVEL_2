<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->bigIncrements('quotation_id');
            //$table->timestamp('quotation_time')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->unsignedBigInteger('employee_id')->index();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->double('sub_total', 15, 3);
            $table->double('tax', 15, 3);
            $table->double('total', 15, 3);
            $table->string('comments', 255)->nullable();
            $table->boolean('status');
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
        Schema::dropIfExists('qutations');
    }
}
