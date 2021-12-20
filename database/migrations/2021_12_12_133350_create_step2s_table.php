<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStep2sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('step2s', function (Blueprint $table) {
            $table->id();
            $table->string('problem');
            $table->string('description_problem');
            $table->string('name_organization');
            $table->string('debt');
            $table->string('loan_data');
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
        Schema::dropIfExists('step2s');
    }
}
