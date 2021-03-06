<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStep1sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('step1s', function (Blueprint $table) {
            $table->id();
            $table->string('fio');
            $table->string('iin');
            $table->string('phone_number');
            $table->string('email');
            $table->string('password');
            $table->string('confirmPhoneNumber');
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
        Schema::dropIfExists('step1s');
    }
}
