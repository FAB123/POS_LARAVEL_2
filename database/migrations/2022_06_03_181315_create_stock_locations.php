<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->bigIncrements('location_id');
            $table->string('location_name_en', 100);
            $table->string('location_name_ar', 100);
            $table->string('location_address_en', 255);
            $table->string('location_address_ar', 255);
            $table->string('location_mobile', 10);
            $table->string('location_email', 50);
            $table->string('location_building_no', 15);
            $table->string('location_street_name_en', 30);
            $table->string('location_street_name_ar', 30);
            $table->string('location_district_en', 30);
            $table->string('location_district_ar', 30);
            $table->string('location_city_en', 30);
            $table->string('location_city_ar', 30);
            $table->string('location_country_en', 30);
            $table->string('location_country_ar', 30);
            $table->string('location_cr_number', 30);
            $table->string('location_vat_number', 20);
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
        Schema::dropIfExists('stock_locations');
    }
}
