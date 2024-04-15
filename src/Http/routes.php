<?php

use Uupt\Approval\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('approval', [Controllers\ApprovalController::class, 'index']);

// 搜索用户
Route::any('/approval/approval_bind',[Controllers\ApprovalBindController::class,'getApprovalBind']);
// 搜索数据
Route::any('/approval/approval_data',[Controllers\ApprovalBindController::class,'getApprovalData']);


// 数据表管理
Route::resource('table_manager', \Uupt\Approval\Http\Controllers\TableManagerController::class);

// 审批流关联
Route::resource('approval_bind', \Uupt\Approval\Http\Controllers\ApprovalBindController::class);

// 审批实例
Route::resource('approval_instance', \Uupt\Approval\Http\Controllers\ApprovalInstanceController::class);

// 获取表列信息
Route::get('/approval_bind-get_table_column',[\Uupt\Approval\Http\Controllers\ApprovalInstanceController::class,'getTableColumn']);

// 新闻发布
Route::resource('news', \Uupt\Approval\Http\Controllers\NewController::class);

