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
            $table->char('name', 10)->comment('来源名称')->unique();
            $table->text('describe')->comment('来源描述')->nullable();
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
            $table->char('name',6)->comment('分类名称');
            $table->char('color', 7)->comment('样式颜色');
            $table->tinyInteger('sort')->comment('排序')->default(99);
            $table->timestamps();
            $table->softDeletes();
        });
        //标签
        Schema::create('tags', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('type_id')->comment('分类id');
            $table->char('name',10)->comment('名称')->unique();
            $table->char('describe', 50)->comment('描述')->nullable();
            $table->tinyInteger('sort')->comment('排序')->nullable();
            $table->foreign('type_id')->references('id')->on('tag_types');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('name',8)->comment('省级')->unique();
        });

        Schema::create('levels',function(Blueprint $table){
            $table->tinyIncrements('id');
            $table->char('name',10)->comment('等级名称');
            $table->string('explain',50)->comment('等级说明')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('linkage',function(Blueprint $table){
            $table->increments('id');
            $table->string('name',15)->comment('地区名字');
            $table->integer('parent_id')->comment('父级id');
            $table->integer('level')->comment('地区登记');
            $table->string('full_name',30);
        });

        //客户表
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 10)->comment('客户姓名')->index();
            $table->unsignedSmallInteger('source_id')->comment('客户来源');
            $table->tinyInteger('status')->comment('客户状态:-1:黑名单，0:潜在客户,1:合作中，2:合作完成');
            $table->char('gender', 1)->comment('性别 ,男,女');
            $table->char('mobile', 11)->comment('电话号码')->unique();
            $table->char('wechat', 20)->comment('微信')->index()->nullable();
            $table->char('nation', 5)->comment('民族');
            $table->char('id_card_number', 18)->comment('身份证号码')->unique();
            $table->char('native_place', 8)->comment('籍贯：省份')->default('')->nullable();
            $table->char('province_id', 6)->comment('现住地址:省')->default('')->nullable();
            $table->char('city_id', 6)->comment('现住地址:市')->default('')->nullable();
            $table->char('county_id', 6)->comment('现住地址:县')->default('')->nullable();
            $table->string('address', 100)->comment('现住地址:详细')->default('')->nullable();
            $table->date('first_cooperation_at')->comment('初次合作时间')->nullable();
            $table->text('icon')->comment('头像')->nullable();
            $table->text('id_card_image_f')->comment('身份证照片正面')->nullable();
            $table->text('id_card_image_b')->comment('身份证照片反面')->nullable();
            $table->char('develop_sn',6)->comment('开发人编号')->index()->nullable();
            $table->char('develop_name',10)->comment('开发人姓名')->index()->nullable();
            $table->integer('recommend_id')->comment('介绍人id')->index()->nullable();
            $table->char('recommend_name',10)->comment('介绍人姓名')->index()->nullable();
            $table->unsignedMediumInteger('vindicator_sn')->comment('维护人编号')->nullable();
            $table->char('vindicator_name', 10)->comment('维护人姓名')->nullable();
            $table->string('remark', 200)->comment('备注')->nullable();
            $table->foreign('source_id')->references('id')->on('source');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_has_linkage',function(Blueprint $table){
            $table->unsignedInteger('client_id')->index();
            $table->unsignedInteger('linkage_id')->index();
            $table->primary(['client_id', 'linkage_id'], 'client_id_province_id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('linkage_id')->references('id')->on('linkage');
        });

        Schema::create('client_has_level',function(Blueprint $table){
            $table->unsignedInteger('client_id')->index();
            $table->unsignedTinyInteger('level_id')->index();
            $table->primary(['client_id', 'level_id'], 'client_id_level_id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('level_id')->references('id')->on('levels');
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
            $table->char('shop_sn', 10)->index();
            $table->primary(['client_id', 'shop_sn'], 'client_id_shop_id');
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
            $table->text('changes')->comment('变动内容');
            $table->tinyInteger('status')->comment('状态 -1:已删除,0:锁定,1:可还原,2:已还原');
            $table->mediumInteger('restore_sn')->comment('还原人编号')->nullable();
            $table->char('restore_name',10)->comment('还原人姓名')->nullable();
            $table->dateTime('restore_at')->comment('还原时间')->nullable();
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_logs');
        Schema::dropIfExists('client_has_shops');
        Schema::dropIfExists('client_has_brands');
        Schema::dropIfExists('client_has_tags');
        Schema::dropIfExists('client_has_level');
        Schema::dropIfExists('client_has_provincial');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('linkage');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('province');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_types');
        Schema::dropIfExists('nations');
        Schema::dropIfExists('source');
    }
}
