<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountOpeningBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_opening_balances', function (Blueprint $table) {
            // $table->id();
            $table->unsignedBigInteger('inserted_by')->index();
            $table->foreign('inserted_by')->references('employee_id')->on('employees');
            $table->unsignedBigInteger('account_id')->index();
            $table->foreign('account_id')->references('account_id')->on('account_heads');
            $table->unsignedBigInteger('account_sub_id')->nullable()->index();
            $table->double('amount', 15, 3)->default(0.00);
            $table->integer('year')->length(4);
            $table->primary(['account_id', 'account_sub_id']);
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
        Schema::dropIfExists('account_opening_balances');
    }
}
