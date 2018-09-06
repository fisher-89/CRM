<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVisitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {//记录类型
        Schema::create('note_types',function(Blueprint $table){
            $table->smallIncrements('id');
            $table->char('name',20)->comment('类型名称');
            $table->tinyInteger('sort')->comment('排序');
            $table->tinyInteger('is_task')->comment('是否为任务（需要处理）0：否 1：是')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        //记录
        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('note_type_id')->comment('类型');
            $table->unsignedInteger('client_id')->comment('客户id')->index();
            $table->char('client_name',10)->comment('客户姓名')->index();
            $table->dateTime('took_place_at')->comment('发生时间');
            $table->unsignedMediumInteger('recorder_sn')->comment('记录人编号')->index();
            $table->char('recorder_name',10)->comment('记录人姓名');
            $table->char('title',20)->comment('标题');
            $table->text('content')->comment('内容');
            $table->text('attachments')->comment('附件地址（数组）')->nullable();
            $table->dateTime('task_deadline')->comment('任务截至时间')->nullable();
            $table->dateTime('finished_at')->comment('任务完成时间')->nullable();
            $table->tinyInteger('task_result')->comment('任务结果 -1：失败，1：成功')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('note_type_id')->references('id')->on('note_types');
            $table->foreign('client_id')->references('id')->on('clients');
        });

        Schema::create('note_logs',function(Blueprint $table){
            $table->increments('id');
            $table->unsignedInteger('note_id')->comment('记录表id');
            $table->string('type',20)->comment('操作类型');
            $table->unsignedMediumInteger('staff_sn')->comment('操作人编号')->index();
            $table->char('staff_name',10)->comment('操作人姓名');
            $table->text('operation_address')->comment('操作地址');
            $table->text('changes')->comment('变动内容');
            $table->timestamps();
            $table->foreign('note_id')->references('id')->on('notes');
        });
        Schema::create('note_has_brand', function (Blueprint $table) {
            $table->unsignedInteger('note_id')->index();
            $table->unsignedSmallInteger('brand_id')->index();
            $table->primary(['note_id', 'brand_id'], 'note_id_brand_id');
            $table->foreign('note_id')->references('id')->on('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('note_logs');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('note_types');
    }
}
