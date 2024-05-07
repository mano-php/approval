<?php

namespace ManoCode\Approval\Observers;

use Illuminate\Support\Facades\DB;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Models\AdminRole;
use Slowlyo\OwlAdmin\Models\AdminUser;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;
use ManoCode\Approval\Library\DataSourcesManager;
use ManoCode\Approval\Models\ApprovalBind;
use ManoCode\Approval\Models\ApprovalInstance;
use UUPT\Corp\Library\DingHttp;
use UUPT\Corp\Models\Employee;

/**
 * 模型观察者
 */
class ModelObserver
{
    public function created($event, $model)
    {
        [$model] = $model;
        /**
         * @var $model Model
         */
        /**
         * 角色自动创建 TODO 转移到组织架构插件内
         */
        if(!($adminRole = AdminRole::query()->where(['name'=>'员工','slug'=>'employees'])->first())){
            $adminRole = new AdminRole();
            $adminRole->setAttribute('name','员工');
            $adminRole->setAttribute('slug','employees');
            $adminRole->setAttribute('created_at',date('Y-m-d H:i:s'));
            $adminRole->save();
        }
        /**
         * 如果是员工创建 判断是否有管理员用户 如果没有自动创建 TODO 转移到组织架构插件内
         */
        if ($model->getTable() === 'employees' && (!AdminUser::query()->where(['username'=>$model->getAttribute('mobile')])->first())) {
            // 创建管理员
            $adminUser = new AdminUser();
            $adminUser->setAttribute('username',$model->getAttribute('mobile'));
            // 用户密码 默认为 手机号
            $adminUser->setAttribute('password',bcrypt($model->getAttribute('mobile')));
            $adminUser->setAttribute('enabled',1);
            $adminUser->setAttribute('name',$model->getAttribute('name'));
            $adminUser->setAttribute('avatar',$model->getAttribute('avatar'));
            $adminUser->setAttribute('created_at',date('Y-m-d H:i:s'));
            $adminUser->save();
            // 绑定角色
            DB::table('admin_role_users')->insert([
                'role_id'=>$adminRole->getAttribute('id'),
                'user_id'=>$adminUser->getAttribute('id'),
                'created_at'=>$adminUser->getAttribute('created_at')
            ]);
        }


        // 判断是否需要发起流程审批
        if (!($approvalBind = ApprovalBind::query()->where(['table_name' => $model->getTable() . '-' . $model->getConnection()->getDatabaseName()])->first())) {
            return;
        }

        // 查询发起人钉钉ID
        if(!($employee = Employee::query()->where('mobile', $userInfo = Admin::user()->getAttribute('username'))->first())){
            $employee = Employee::query()->first();
        }

        $formComponentValueList = [];
        foreach (json_decode($approvalBind->getAttribute('columns'), true) as $key => $column) {
            if (in_array($column['name'], ['status', 'state'])) {
                continue;
            }
            $value = $model->getAttribute($column['name']);
            $formComponentValueList[] = [
                'name' => $column['comment'],
                'value' => strval(strlen($value) >= 1 ? $value : ''),
            ];
        }
        $requestData = [
            'processCode' => $approvalBind->getAttribute('process_code'), // 审批流程
            'originatorUserId' => $employee->getAttribute('dingtalk_id'), // 发起人
            'formComponentValues' => $formComponentValueList, // 字段列表
            'title' => $approvalBind->getAttribute('name'), // 标题
            'url' => $approvalBind->getAttribute('url'), // 审批URl
            'deptId' => '-1', // 部门
        ];
        /**
         * 发起流程
         */
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/processInstances', $requestData);
        /**
         * 存储实例ID
         */
        if (isset($response['instanceId']) && strlen(strval($response['instanceId'])) >= 1) {
            $approvalInstance = new ApprovalInstance();
            $approvalInstance->setAttribute('process_instance_id', $response['instanceId']);
            $approvalInstance->setAttribute('approval_bind_id', $approvalBind->getAttribute('id'));
            $approvalInstance->setAttribute('originator_user_id', $employee->getAttribute('id'));
            $approvalInstance->setAttribute('title', $approvalBind->getAttribute('name'));
            $approvalInstance->setAttribute('url', 'https://www.baidu.com');
            $approvalInstance->setAttribute('approval_bind_data', json_encode($model->getAttributes()));
            $approvalInstance->save();
        } else {
            // TODO 异常情况处理
            dump($requestData);
            dd($response);
        }
    }

    public function saved($event, $model)
    {
        if(!preg_match('/eloquent\.('.__FUNCTION__.'):\s(.+)/',$event,$modelClass)){
            return ;
        }
        [,$eventStr,$modelStr] = $modelClass;
        [$model] = $model;
        DataSourcesManager::getInstance()->triggerEvent($eventStr,$model);
        return ;
        /**
         * @var $model Model
         */
        if (!($approvalBind = ApprovalBind::query()->where(['table_name' => $model->getTable() . '-' . $model->getConnection()->getDatabaseName()])->first())) {
            return;
        }

        // 获取发起人
        if(!($employee = Employee::query()->where('mobile', $userInfo = Admin::user()->getAttribute('username'))->first())){
            $employee = Employee::query()->first();
        }

        $formComponentValueList = [];
        foreach (json_decode($approvalBind->getAttribute('columns'), true) as $key => $column) {
            if (in_array($column['name'], ['status', 'state'])) {
                continue;
            }
            $value = $model->getAttribute($column['name']);
            $formComponentValueList[] = [
                'name' => $column['comment'],
                'value' => strval(strlen($value) >= 1 ? $value : ''),
            ];
        }
        $requestData = [
            'processCode' => $approvalBind->getAttribute('process_code'), // 审批流程
            'originatorUserId' => $employee->getAttribute('dingtalk_id'), // 发起人
            'formComponentValues' => $formComponentValueList, // 字段列表
            'title' => $approvalBind->getAttribute('name'), // 标题
            'url' => $approvalBind->getAttribute('url'), // 审批URl
            'deptId' => '-1', // 部门
        ];
        /**
         * 发起流程
         */
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/processInstances', $requestData);
        /**
         * 存储实例ID
         */
        if (isset($response['instanceId']) && strlen(strval($response['instanceId'])) >= 1) {
            $approvalInstance = new ApprovalInstance();
            $approvalInstance->setAttribute('process_instance_id', $response['instanceId']);
            $approvalInstance->setAttribute('approval_bind_id', $approvalBind->getAttribute('id'));
            $approvalInstance->setAttribute('originator_user_id', $employee->getAttribute('id'));
            $approvalInstance->setAttribute('title', $approvalBind->getAttribute('name'));
            $approvalInstance->setAttribute('url', 'https://www.baidu.com');
            $approvalInstance->setAttribute('approval_bind_data', json_encode($model->getAttributes()));
            $approvalInstance->save();
        } else {
            // TODO 异常情况处理
            dump($requestData);
            dd($response);
        }
    }
}
