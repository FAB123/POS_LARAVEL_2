<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountLedgerEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->foreign('transaction_id')->references('transaction_id')->on('accounts_transactions');
            $table->unsignedBigInteger('account_id')->index();
            $table->foreign('account_id')->references('account_id')->on('account_heads');
            $table->enum('entry_type', ['C', 'D']);
            $table->double('amount', 15, 3);
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->enum('person_type', ['C', 'S', 'E'])->nullable();;
            $table->enum('cost_centre', ['S', 'P'])->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_ledger_entries');
    }
}
