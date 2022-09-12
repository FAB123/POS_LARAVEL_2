<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts_transactions', function (Blueprint $table) {
            $table->bigIncrements('transaction_id');
            $table->enum('transaction_type', ['S', 'P', 'TJ', 'TR', 'TP']);
            $table->string('document_no', 100);
            $table->unsignedBigInteger('inserted_by')->index();
            $table->foreign('inserted_by')->references('employee_id')->on('employees');
            $table->string('description', 255);
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
        Schema::dropIfExists('accounts_transactions');
    }
}
