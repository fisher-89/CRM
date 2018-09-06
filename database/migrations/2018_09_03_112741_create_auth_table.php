<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //权限分组
        Schema::create('authority_groups', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('name',20)->comment('分组名称');
            $table->char('description',30)->comment('描述')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        //员工分组

        Schema::create('auth_group_has_visible_brands', function (Blueprint $table) {
            $table->unsignedTinyInteger('authority_group_id');
            $table->unsignedMediumInteger('brand_id')->comment('品牌id')->index();
            $table->primary(['authority_group_id', 'brand_id'], 'authority_group_brand_id');
            $table->foreign('authority_group_id')->references('id')->on('authority_groups');
        });

        Schema::create('auth_group_has_editable_brands', function (Blueprint $table) {
            $table->unsignedTinyInteger('authority_group_id');
            $table->unsignedMediumInteger('brand_id')->comment('品牌id')->index();
            $table->primary(['authority_group_id', 'brand_id'], 'authority_group_brand_id');
            $table->foreign('authority_group_id')->references('id')->on('authority_groups');
        });

        Schema::create('auth_group_has_staff', function (Blueprint $table) {
            $table->unsignedTinyInteger('authority_group_id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号')->index();
            $table->primary(['authority_group_id', 'staff_sn'], 'authority_group_staff_sn');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->foreign('authority_group_id')->references('id')->on('authority_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_group_has_staff');
        Schema::dropIfExists('auth_group_has_editable_brands');
        Schema::dropIfExists('auth_group_has_visible_brands');
        Schema::dropIfExists('authority_groups');
    }
}
