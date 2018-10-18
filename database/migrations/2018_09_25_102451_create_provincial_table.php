<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProvincialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provincial', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name',8)->comment('省级')->unique();
        });
        Schema::create('linkage',function(Blueprint $table){
            $table->increments('id');
            $table->string('name',15)->comment('地区名字');
            $table->integer('parent_id')->comment('父级id');
            $table->integer('level')->comment('地区登记');
            $table->string('full_name',30);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provincial');
    }
}
