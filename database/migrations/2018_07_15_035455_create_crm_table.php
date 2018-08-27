<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {//来源
        Schema::create('source', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->char('name', 10)->comment('来源名称');
            $table->text('describe')->comment('来源描述');
            $table->tinyInteger('sort')->comment('排序')->default(99);
        });
        //民族
        Schema::create('nations', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->char('name', 5)->comment('民族名称')->unique();
            $table->tinyInteger('sort')->comment('排序')->default(99);
        });
        //标签类型
        Schema::create('tag_types', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name')->comment('分类名称');
            $table->char('color', 7)->comment('样式颜色');
            $table->tinyInteger('sort')->comment('排序');
            $table->timestamps();
        });
        //标签
        Schema::create('tags', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('type_id')->comment('分类id');
            $table->char('name',10)->comment('名称');
            $table->char('describe', 50)->comment('描述');
            $table->tinyInteger('sort')->comment('排序');
            $table->foreign('type_id')->references('id')->on('tag_types');
            $table->timestamps();
            $table->softDeletes();
        });

        //权限分组
        Schema::create('authority_groups', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('name',20)->comment('分组名称');
            $table->tinyInteger('auth_type')->comment('权限类型,1:查看,2:操作')->index();
            $table->tinyInteger('auth_brand')->comment('权限品牌')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        //客户表
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 10)->comment('客户姓名')->index();
            $table->unsignedSmallInteger('source_id')->comment('客户来源');
            $table->tinyInteger('status')->comment('客户状态:-1:黑名单，0:潜在客户,1:合作中，2:合作完成');
            $table->char('gender', 1)->comment('性别 ,男,女');
            $table->char('mobile', 14)->comment('电话号码')->unique();
            $table->char('wechat', 20)->comment('微信')->index()->nullable();
            $table->char('nation', 5)->comment('民族');
            $table->char('id_card_number', 18)->comment('身份证号码')->unique();
            $table->char('native_place', 8)->comment('籍贯：省份')->default('');
            $table->char('present_address', 50)->comment('现住地址')->default('')->nullable();
            $table->dateTime('first_cooperation_at')->comment('初次合作时间');
            $table->unsignedMediumInteger('vindicator_sn')->comment('维护人编号')->nullable();
            $table->char('vindicator_name', 10)->comment('维护人姓名')->nullable();
            $table->char('remark', 200)->comment('备注')->nullable();
            $table->foreign('source_id')->references('id')->on('source');
            $table->timestamps();
            $table->softDeletes();
        });
        //客户管理标签中间表
        Schema::create('client_has_tags', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->index();
            $table->unsignedSmallInteger('tag_id')->index();
            $table->primary(['client_id', 'tag_id'], 'client_id_tag_id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('tag_id')->references('id')->on('tags');
        });
        //品牌中间表
        Schema::create('client_has_brands', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->index();
            $table->unsignedInteger('brand_id')->index();
            $table->primary(['client_id', 'brand_id'], 'client_id_brand_id');
            $table->foreign('client_id')->references('id')->on('clients');
        });
        //店铺中间表
        Schema::create('client_has_shops', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->index();
            $table->char('shop_id', 10)->index();
            $table->primary(['client_id', 'shop_id'], 'client_id_shop_id');
            $table->foreign('client_id')->references('id')->on('clients');
        });
        //客户资料记录
        Schema::create('client_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('client_id')->index();
            $table->string('type', 20)->comment('操作类型');
            $table->unsignedMediumInteger('staff_sn')->comment('操作人编号')->index();
            $table->char('staff_name', 10)->comment('操作人姓名');
            $table->text('operation_address')->comment('操作地址');
            $table->text('alteration_content')->comment('变动内容');
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients');
        });
        //员工分组
        Schema::create('client_group_staff', function (Blueprint $table) {
            $table->unsignedTinyInteger('authority_group_id');
            $table->unsignedMediumInteger('staff_sn')->comment('员工编号')->index();
            $table->primary(['authority_group_id', 'staff_sn'], 'authority_group_staff_sn');
            $table->char('staff_name', 10)->comment('员工姓名');
            $table->foreign('authority_group_id')->references('id')->on('authority_groups');
        });
        //部门分组
        Schema::create('client_group_departments', function (Blueprint $table) {
            $table->unsignedTinyInteger('authority_group_id');
            $table->unsignedSmallInteger('department_id')->comment('部门id')->index();
            $table->primary(['authority_group_id', 'department_id'], 'authority_group_department_id');
            $table->char('department_name',10)->comment('部门名称');
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
        Schema::dropIfExists('authority_group_departments');
        Schema::dropIfExists('authority_group_staff');
        Schema::dropIfExists('client_logs');
        Schema::dropIfExists('client_has_shops');
        Schema::dropIfExists('client_has_brands');
        Schema::dropIfExists('client_has_tags');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('authority_groups');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_types');
        Schema::dropIfExists('nations');
        Schema::dropIfExists('source');
    }
}
