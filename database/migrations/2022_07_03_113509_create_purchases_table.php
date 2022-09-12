<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->bigIncrements('purchase_id');
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers');
            $table->unsignedBigInteger('employee_id')->index();
            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->enum('purchase_type', ['CAP', 'CAPR', 'CRP', 'CRPR'])->index();
            $table->string('pic_filename', 255)->nullable();
            $table->string('reference', 32)->nullable();
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
        Schema::dropIfExists('purchases');
    }
}
