<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('item_id');
            $table->string('item_name', 255);
            $table->string('item_name_ar', 255)->nullable();
            $table->string('barcode', 255)->unique()->nullable();
            $table->string('category', 255);
            $table->string('shelf', 50)->nullable();
            $table->double('cost_price', 15, 3);
            $table->double('unit_price', 15, 3)->nullable();
            $table->double('wholesale_price', 15, 3)->nullable();
            $table->double('minimum_price', 15, 3)->nullable();
            $table->string('pic_filename', 255)->nullable();
            $table->boolean('allowdesc')->default(0)->comment('1 : No Description, 0: Description');
            $table->boolean('is_serialized')->default(0)->comment('1 : No Serial, 0: Serialized');
            $table->integer('reorder_level')->default(0)->nullable();
            $table->boolean('stock_type')->default(1)->comment('1 : stocked, 0: non stocked');
            $table->boolean('unit_type')->default(1);
            $table->boolean('is_boxed')->default(0)->comment('1 : boxed item, 0: normal items');
            $table->string('description', 255)->nullable();
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
        Schema::dropIfExists('items');
    }
}
