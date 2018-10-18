<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar;

Route::options('{a?}/{b?}/{c?}', function () {
    return response('', 204);
});
Route::group(['middleware' => 'auth:api'], function (Registrar $admin) {
    //标签类型
    $admin->get('tags/types', Admin\TagController::class . '@indexType');
    $admin->post('tags/types', Admin\TagController::class . '@storeType');
    $admin->put('tags/types/{id}', Admin\TagController::class . '@updateType');
    $admin->delete('tags/types/{id}', Admin\TagController::class . '@deleteType');
    $admin->get('tags', Admin\TagController::class . '@index');
    $admin->post('tags', Admin\TagController::class . '@store');
    $admin->put('tags/{id}', Admin\TagController::class . '@update');
    $admin->delete('tags/{id}', Admin\TagController::class . '@delete');
    //来源
    $admin->get('source', Admin\SourceController::class . '@index');
    $admin->post('source', Admin\SourceController::class . '@store');
    $admin->put('source/{id}', Admin\SourceController::class . '@update');
    $admin->delete('source/{id}', Admin\SourceController::class . '@delete');
    //客户资料
    $admin->get('clients', Admin\ClientsController::class . '@index');
    $admin->post('clients', Admin\ClientsController::class . '@store');
    $admin->put('clients/{id}', Admin\ClientsController::class . '@update');
    $admin->delete('clients/{id}', Admin\ClientsController::class . '@delete');
    $admin->get('clients/{id}', Admin\ClientsController::class . '@details');
    $admin->get('clients/export', Admin\ClientsController::class . '@export');//导出
    $admin->post('clients/import', Admin\ClientsController::class . '@import');//导入 todo  导入人权限品牌验证，合作店铺，合作品牌，合作区域   没弄
    $admin->post('clients/image',Admin\FilesController::class.'@iconImage');//头像
    $admin->post('clients/card',Admin\FilesController::class.'@cardImage');//身份证照片

    $admin->get('clients/brands',Admin\AuthBrandController::class.'@getBrand');
    //民族选择
    $admin->get('nation', Admin\NationController::class . '@index');//获取民族
    $admin->post('nation', Admin\NationController::class . '@store');//todo 临时用接口
    //客户资料-log
    $admin->get('client/logs', Admin\ClientLogsController::class . '@index');
    $admin->get('client/logs/{id}', Admin\ClientLogsController::class . '@restore');
    //客户事件
    $admin->get('note/type', Admin\NotesController::class . '@indexType');
    $admin->post('note/type', Admin\NotesController::class . '@storeType');//todo 多个模板字段合并  后期
    $admin->put('note/type/{id}', Admin\NotesController::class . '@editType');
    $admin->delete('note/type/{id}', Admin\NotesController::class . '@deleteType');
    $admin->get('notes', Admin\NotesController::class . '@index');
    $admin->post('notes', Admin\NotesController::class . '@store');
    $admin->put('notes/{id}', Admin\NotesController::class . '@edit');
    $admin->delete('notes/{id}', Admin\NotesController::class . '@delete');//todo 还没有做废弃文件夹定时清理
    $admin->get('notes/{id}', Admin\NotesController::class . '@detailNote');
    $admin->get('notes/brand/{id}',Admin\NotesController::class.'@getUserBrands');
    //临时文件存储
    $admin->post('notes/files', Admin\FilesController::class . '@index');
    //客户事件记录
    $admin->get('note/logs', Admin\NoteLogsController::class . '@index');
    $admin->get('note/logs/{id}', Admin\NoteLogsController::class . '@restore');
    //权限
    $admin->get('auth', Admin\AuthorityController::class . '@index');
    $admin->post('auth', Admin\AuthorityController::class . '@store');
    $admin->put('auth/{id}', Admin\AuthorityController::class . '@edit');
    $admin->delete('auth/{id}', Admin\AuthorityController::class . '@delete');
});
Route::get('clients/example', Admin\ClientsController::class . '@example');//客户信息导入模板
//localhost:8004/admin/clients/example
// 客户事件  编辑接口
// 权限4个接口
