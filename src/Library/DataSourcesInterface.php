<?php

namespace ManoCode\Approval\Library;
use Illuminate\Database\Eloquent\Model;

/**
 * 钉钉审批数据源
 */
interface DataSourcesInterface
{
    /**
     * 获取流程code
     * @return string
     */
    public function getProcessCode(): string;
    /**
     * 获取数据源名称
     * @return string
     */
    public function getName(): string;
    /**
     * 获取数据源描述
     * @return string
     */
    public function getDescription(): string;
    /**
     * 获取模型列表
     * @return string
     */
    public function getModel(): string;

    /**
     * 获取表列表
     * @return string
     */
    public function getTable(): string;

    /**
     * 获取钉钉审批表单 组件参考 https://open.dingtalk.com/document/orgapp/create-or-update-approval-templates-new#h2-imr-6km-isq
     * @return array
     */
    public function getFormComponentsStruct():array;

    /**
     * 获取表单组件值
     * @param Model $model
     * @return array
     */
    public function getFormComponentsValue(Model $model):array;

    /**
     * 获取监听事件
     * @return array
     */
    public function getListenEvent():array;

    /**
     * 审批通过
     * @param Model $model
     * @return void
     */
    public function pass(Model $model):void;

    /**
     * 审批拒绝时的操作
     * @param Model $model
     * @return void
     */
    public function reject(Model $model):void;

    /**
     * 等待审批
     * @param Model $model
     * @return void
     */
    public function wait(Model $model):void;
}
