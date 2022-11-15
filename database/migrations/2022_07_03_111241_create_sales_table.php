<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('sale_id');
            // $table->timestamp('sale_time')->index();
            $table->unsignedBigInteger('customer_id')->index()->nullable();
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->unsignedBigInteger('employee_id')->index();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->boolean('sale_status')->default(0)->comment('1 : Compleeted, 0: Created');
            $table->enum('bill_type', ['B2C', 'B2B'])->index();
            $table->enum('sale_type', ['CAS', 'CASR', 'CRS', 'CRSR'])->index();
            $table->unsignedBigInteger('table_id')->index()->nullable();
            $table->foreign('table_id')->references('table_id')->on('dinner_tables');
            $table->double('sub_total', 15, 3);
            $table->double('tax', 15, 3);
            $table->double('discount', 15, 3);
            $table->double('total', 15, 3);
            $table->string('comments', 255)->nullable();
            // $table->boolean('status');
            $table->unsignedBigInteger('location_id')->index();
            $table->foreign('location_id')->references('location_id')->on('stock_locations');
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
        Schema::dropIfExists('sales');
    }
}
