<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGaztDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gazt_datas', function (Blueprint $table) {
            $table->id();
            $table->binary('production_certificate');
            $table->binary('production_key');
            $table->binary('hash');
            $table->binary('issuer');
            $table->binary('serial_number');
            $table->binary('private_key');
            $table->binary('public_key');
            $table->binary('signature');
            $table->string('activated', 165);
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
        Schema::dropIfExists('gazt_data');
    }
}
