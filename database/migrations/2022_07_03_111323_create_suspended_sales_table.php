<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuspendedSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suspended_sales', function (Blueprint $table) {
            $table->bigIncrements('suspended_id');
            $table->unsignedBigInteger('customer_id')->index()->nullable();
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->unsignedBigInteger('employee_id')->index();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->boolean('suspended_status')->default(0)->comment('1 : unsuspended, 0: suspended');
            $table->enum('bill_type', ['B2C', 'B2B'])->index();
            $table->enum('sale_type', ['CAS', 'CASR', 'CRS', 'CRSR'])->index();
            $table->unsignedBigInteger('table_id')->index()->nullable();
            $table->foreign('table_id')->references('table_id')->on('dinner_tables');
            $table->double('sub_total', 15, 3);
            $table->double('tax', 15, 3);
            $table->double('total', 15, 3);
            $table->string('comments', 255)->nullable();
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
        Schema::dropIfExists('suspended_sales');
    }
}
