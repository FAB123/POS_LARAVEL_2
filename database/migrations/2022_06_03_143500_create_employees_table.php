<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('employee_id');
            $table->string('name', 255);
            $table->string('username', 30)->unique();
            $table->string('password', 255);
            $table->rememberToken();
            $table->string('address_line_1', 255);
            $table->string('mobile', 255);
            $table->string('email', 255);
            $table->string('comments', 255)->nullable();
            $table->enum('lang', ['en', 'ar', 'ur'])->default('en');
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
        Schema::dropIfExists('employees');
    }
}
