<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->bigIncrements('supplier_id');
            $table->string('name', 255);
            $table->string('mobile', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('vat_number', 20)->nullable();
            $table->integer('payment_type')->nullable();
            $table->boolean('taxable')->default(1)->comment('1 : Active, 0: Disabled');
            $table->boolean('status')->default(1)->comment('1 : Active, 0: Disabled');
            $table->softDeletes();
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
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
}
