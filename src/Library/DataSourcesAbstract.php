<?php

namespace ManoCode\Approval\Library;
use ManoCode\Approval\Models\News;
use ManoCode\Approval\Models\ProcessCodeBind;

/**
 * 数据源抽象类
 */
abstract class DataSourcesAbstract implements DataSourcesInterface
{
    /**
     * 审批模板ID（如果为空则自动创建模板）
     * @var string|null
     */
    protected string|null $processCode = null;

    /**
     * 获取流程code
     * @return string
     */
    public function getProcessCode(): string
    {
        if(strlen($this->processCode)>=1){
            return $this->processCode;
        }
        if(!($processCodeBind = ProcessCodeBind::query()->where(['data_sources'=>get_class($this)])->first())){
            return '';
        }
        return $processCodeBind->getAttribute('process_code');
    }
    public function getTable(): string
    {
        $modelClass = $this->getModel();
        return (new $modelClass())->getTable();
    }
    /**
     * 获取数据源名称
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }
    public function getListenEvent(): array
    {
        return [
            'created',
            'saved'
        ];
    }

    /**
     * 获取数据源描述
     * @return string
     */
    public function getDescription(): string
    {
        return static::class;
    }
}
