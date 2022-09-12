<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasicMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basic_menus', function (Blueprint $table) {
            $table->string('menu_id');
            $table->foreign('menu_id')->references('menu_id')->on('menus');
            $table->string('permission_id')->primary();
            $table->foreign('permission_id')->references('permission_id')->on('permissions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('basic_menus');
    }
}
