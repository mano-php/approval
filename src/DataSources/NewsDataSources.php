<?php

namespace mano-code\Approval\DataSources;

use Illuminate\Database\Eloquent\Model;
use mano-code\Approval\Library\DataSourcesAbstract;
use mano-code\Approval\Models\News;

/**
 * 新闻数据源
 */
class NewsDataSources extends DataSourcesAbstract
{
//    protected string|null $processCode = 'PROC-6CF3AC68-7306-4E77-A8EF-FFB01F118C6E';
    /**
     * 获取数据源名称
     * @return string
     */
    public function getName(): string
    {
        return '新闻管理';
    }

    /**
     * 获取数据源描述
     * @return string
     */
    public function getDescription(): string
    {
        return '新闻变动审批';
    }
    public function getModel(): string
    {
        return News::class;
    }
    public function getTable(): string
    {
        return (new News())->getTable();
    }
    public function getFormComponentsStruct():array
    {
        return [
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'title',
                    'label'=>'标题',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'desc',
                    'label'=>'描述',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'content',
                    'label'=>'新闻内容',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
        ];
    }

    /**
     * 获取表单组件值
     * @return array[]
     */
    public function getFormComponentsValue(Model $model):array
    {
        return [
            [
                'name'=>'标题',
                'value'=>$model->getAttribute('title')
            ],
            [
                'name'=>'描述',
                'value'=>$model->getAttribute('desc')
            ],
            [
                'name'=>'新闻内容',
                'value'=>$model->getAttribute('content')
            ],
        ];
    }

    /**
     * 审批通过时的操作
     * @param Model $model
     * @return void
     */
    public function pass(Model $model):void
    {
        // 审批通过时的操作
        $model->setAttribute('status',2);
        $model->save();
    }

    /**
     * 审批拒绝时的操作
     * @param Model $model
     * @return void
     */
    public function reject(Model $model):void
    {
        // 审批拒绝时的操作
        $model->setAttribute('status',3);
        $model->save();
    }

    /**
     * 等待审批
     * @param Model $model
     * @return void
     */
    public function wait(Model $model):void
    {
        // 等待审批
        $model->setAttribute('status',1);
        $model->save();
    }
}
