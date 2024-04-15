<?php

namespace Uupt\Approval\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Slowlyo\OwlAdmin\Admin;
use Uupt\Approval\Models\ApprovalBind;
use Uupt\Approval\Models\InstanceBind;
use Uupt\Approval\Models\ProcessCodeBind;
use UUPT\Corp\Library\DingHttp;
use UUPT\Corp\Models\Employee;

/**
 * 数据来源管理
 */
class DataSourcesManager
{
    /**
     * @var DataSourcesInterface[]
     */
    protected array $sources = [];
    /**
     * 是否开启拦截
     * @var bool
     */
    protected static bool $beginHook = true;
    /**
     * @var DataSourcesManager|null
     */
    protected static ?DataSourcesManager $instance = null;

    /**
     * 是否开启拦截
     * @return bool
     */
    public static function isBeginHook(): bool
    {
        return self::$beginHook;
    }

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @throws \Exception
     */
    public function notify(string $processCode, string $processInstanceId, string $type, string $result): void
    {
        if ($type !== 'finish') {
            return;
        }
        $dataSource = null;
        foreach ($this->sources as $item) {
            if ($item->getProcessCode() === $processCode) {
                $dataSource = $item;
            }
        }
        if ($dataSource === null) {
            throw new \Exception("{$processCode} 找不到处理逻辑");
        }
        /**
         * @var $query Builder
         */
        $query = ($dataSource->getModel())::query();

        /**
         * @var $modelObject Model
         */
        $modelObject = new ($dataSource->getModel());
        if (!($model = $query->where($modelObject->getKeyName(), InstanceBind::query()->where(['instance_id' => $processInstanceId])->value('key_id'))->first())) {
            throw new \Exception("实例:{$processInstanceId}数据不存在");
        }
        try {
            self::$beginHook = false;
            // 查询数据
            if ($result === 'agree') {
                $dataSource->pass($model);
            } else {
                $dataSource->reject($model);
            }
        } catch (\Throwable $throwable) {
            self::$beginHook = true;
            Log::error('审批回调数据处理失败',[
                'message'=>$throwable->getMessage(),
                'file'=>$throwable->getFile(),
                'line'=>$throwable->getLine(),
                'trace'=>$throwable->getTraceAsString(),
            ]);
        }
    }

    /**
     * 注册数据来源
     * @param DataSourcesInterface $instance
     * @return $this
     */
    public function registerDataSources(DataSourcesInterface $instance): static
    {
        $this->sources[$instance::class] = $instance;
        return $this;
    }

    /**
     * 触发事件
     * @param string $event
     * @param Model $model
     * @return void
     */
    public function triggerEvent(string $event, Model $model): void
    {
        foreach ($this->sources as $item) {
            if ($item->getModel() === get_class($model)) {
                if (in_array($event, $item->getListenEvent())) {
                    if (strlen(strval($item->getProcessCode())) <= 0) {
                        // 创建流程
                        $this->createOrUpdate($item);
                    }
                    // 发起审批
                    $this->approval($model, $item);
                }
            }
        }
    }

    /**
     * 如果没有指定模板则自动创建（默认不常用）
     * @param DataSourcesInterface $dataSources
     * @return void
     */
    protected function createOrUpdate(DataSourcesInterface $dataSources): void
    {
        $requestData = [
            'name' => $dataSources->getName(),
            'description' => $dataSources->getDescription(),
            'formComponents' => $dataSources->getFormComponentsStruct(),
            'templateConfig' => [
                'disableStopProcessButton' => true,// 管理列表页是否禁用停用按钮。
                'hidden' => false,// 是否全局隐藏流程模板入口。
                'disableDeleteProcess' => true,// 是否禁用模板删除按钮。
                'disableFormEdit' => false,// 是否禁止表单编辑功能。
                'disableResubmit' => false,// 是否禁用详情页再次发起按钮。
                'disableHomepage' => false,// 是否在首页隐藏模板。
            ],
        ];
        // 更新模板
        if (strlen($dataSources->getProcessCode()) >= 1) {
            $requestData['processCode'] = $dataSources->getProcessCode();
        }
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/forms', $requestData);
        if (isset($response['result']['processCode']) && strlen(strval($response['result']['processCode'])) >= 1) {
            /**
             * 修改模板
             */
            if (!($processCodeBind = ProcessCodeBind::query()->where(['process_code' => strval($response['result']['processCode'])])->first())) {
                $processCodeBind = new ProcessCodeBind();
                $processCodeBind->setAttribute('created_at', date('Y-m-d H:i:s'));
                $processCodeBind->setAttribute('data_sources', get_class($dataSources));
            }
            $processCodeBind->setAttribute('process_code', strval($response['result']['processCode']));
            $processCodeBind->save();
        } else {
            Log::error('审批模板创建失败' . serialize($response));
        }
    }

    /**
     * 发起审批
     * @param Model $model
     * @param DataSourcesInterface $dataSources
     * @return void
     */
    protected function approval(Model $model, DataSourcesInterface $dataSources): void
    {
        /**
         * @var $model \Slowlyo\OwlAdmin\Models\BaseModel
         */
        // 获取发起人
        if (!($employee = Employee::query()->where('mobile', Admin::user()->getAttribute('username'))->first())) {
            $employee = Employee::query()->first();
        }
        /**
         * 拼接数据
         */
        $requestData = [
            'processCode' => $dataSources->getProcessCode(), // 审批流程
            'originatorUserId' => $employee->getAttribute('dingtalk_id'), // 发起人
            'formComponentValues' => $dataSources->getFormComponentsValue($model), // 字段列表
            'title' => $dataSources->getName(), // 标题
            'url' => 'https://www.baidu.com', // 审批URl
            'deptId' => '-1', // 部门
        ];
        try {
            self::$beginHook = false;
            $dataSources->wait($model);
        } catch (\Throwable $throwable) {
            self::$beginHook = true;
            Log::error('设置等待审核状态失败:' . serialize([
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => $throwable->getTraceAsString(),
                ]));
        }
        /**
         * 发起流程
         */
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/processInstances', $requestData);
        if (isset($response['instanceId']) && strval($response['instanceId']) >= 1) {
            // 审批发起成功
            $instanceBind = new InstanceBind();
            $instanceBind->setAttribute('instance_id', $response['instanceId']);
            $instanceBind->setAttribute('key_id', $model->getAttribute('id'));
            $instanceBind->setAttribute('created_at', date('Y-m-d H:i:s'));
            $instanceBind->save();
            Log::info("审批:" . get_class($dataSources) . ',发起成功。' . serialize($response));
        } else {
            Log::error("审批:" . get_class($dataSources) . ',发起失败。' . serialize($response));
        }
    }

    /**
     * 移除数据来源
     * @param DataSourcesInterface $instance
     * @return $this
     */
    public function removeDataSources(DataSourcesInterface $instance): static
    {
        if (isset($this->sources[$instance::class])) {
            unset($this->sources[$instance::class]);
        }
        return $this;
    }
}
