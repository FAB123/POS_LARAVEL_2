<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountHeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_heads', function (Blueprint $table) {
            $table->bigIncrements('account_id');
            $table->string('account_name', 255);
            $table->string('account_name_ar', 255)->nullable();
            $table->smallInteger("account_type");
            //$table->string('official_code',255);
            $table->unsignedBigInteger('inserted_by')->index();
            $table->boolean("editable")->default(1);
            $table->foreign('inserted_by')->references('employee_id')->on('employees');
            $table->string('comments', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_heads');
    }
}
