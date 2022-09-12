<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDinnerTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dinner_tables', function (Blueprint $table) {
            $table->bigIncrements('table_id');
            $table->string('table_name_en', 100);
            $table->string('table_name_ar', 100);
            $table->boolean('status')->default(1)->comment('1 : Enable, 0: Disabled');
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
        Schema::dropIfExists('dinner_tables');
    }
}
