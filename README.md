### 钉钉审批模块


#### 1. 需要首先在  app/Providers/EventServiceProvider::$listen 内新增监听

```php
    
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ...........
        \ManoCode\Corp\Events\DingNotify\BpmsTaskChangeEvent::class=>[
            \ManoCode\Approval\Listeners\ApprovalEventHandler::class,
        ],
        // ...........
    ];
```
#### 2. 定义审批数据源类 并且 在业务逻辑开始前 注册数据源

> 定义数据源

```php
<?php

namespace YourNameSpace;

use Illuminate\Database\Eloquent\Model;
use ManoCode\Approval\Library\DataSourcesAbstract;

/**
 * 采购审批
 */
class PurchaseDataSources extends DataSourcesAbstract
{
    /**
     * 监听的事件 这里可以自定义事件 或者 created、saved 默认就是 created、saved 默认事件会在model数据操作变更时自动触发
     * 如果需要手动提审则需要去掉 默认事件并且 自定义事件
     *
     * @return string[]
     */
    public function getListenEvent(): array
    {
        return [
            'push-process'
        ];
    }

    /**
     * 流程名称
     * @return string
     */
    public function getName():string
    {
        return '采购审批';
    }

    /**
     * 流程描述
     * @return string
     */
    public function getDescription():string
    {
        return '采购审批';
    }

    /**
     * 获取数据模型 也就是需要审批的主表模型
     * @return string
     */
    public function getModel(): string
    {
        return MemberAccount::class;
    }

    /**
     * 审批模板（自动创建时 审批模板时 使用 可以参考钉钉文档 ）
     * https://open.dingtalk.com/document/orgapp/create-or-update-approval-templates-new#h2-imr-6km-isq
     * @return array[]
     */
    public function getFormComponentsStruct(): array
    {
        return [
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'order_no',
                    'label'=>'采购单号',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'company',
                    'label'=>'供应商',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'pay_type',
                    'label'=>'付款方式',
                    'required'=>true, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'admin_remarks',
                    'label'=>'管理备注',
                    'required'=>false, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'order_remarks',
                    'label'=>'采购单备注',
                    'required'=>false, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'contacts',
                    'label'=>'联系人',
                    'required'=>false, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
            [
                'componentType'=>'TextField',
                'props'=>[
                    'componentId'=>'mobile',
                    'label'=>'联系电话',
                    'required'=>false, // 必填
                    'disabled'=>false, // 不允许修改
                ]
            ],
        ];
    }

    /**
     * 审批模板数据填充 （审批发起时、自动填充数据。可连表、判断循环等操作）
     * @param Model $model
     * @return array[]
     */
    public function getFormComponentsValue(Model $model): array
    {
        return [
            [
                'name'=>'采购单号',
                'value'=>strval($model->getAttribute('order_no'))
            ],
            [
                'name'=>'供应商',
                'value'=>strval(Company::query()->where('id',$model->getAttribute('company'))->value('name'))
            ],
            [
                'name'=>'付款方式',
                'value'=>strval(array_column(erp_admin_dict_options('purchase.pay_type'),'label','value')[$model->getAttribute('pay_type')])
            ],
            [
                'name'=>'采购单备注',
                'value'=>strval($model->getAttribute('order_remarks'))
            ],
            [
                'name'=>'管理备注',
                'value'=>strval($model->getAttribute('admin_remarks'))
            ],
            [
                'name'=>'联系人',
                'value'=>strval($model->getAttribute('contacts'))
            ],
            [
                'name'=>'联系电话',
                'value'=>strval($model->getAttribute('mobile'))
            ],
        ];
    }

    /**
     * 审批通过 的回调
     * @param Model $model
     * @return void
     */
    public function pass(Model $model): void
    {
        $model->setAttribute('status',1);
        $model->setAttribute('pass_time',date('Y-m-d H:i:s'));
        $model->setAttribute('start_purchase',date('Y-m-d H:i:s'));
        $model->save();
    }

    /**
     * 审批拒绝时的 回调
     * @param Model $model
     * @return void
     */
    public function reject(Model $model): void
    {
        $model->setAttribute('status',4);
        $model->save();
    }

    /**
     * 当发起审批时的回调
     * @param Model $model
     * @return void
     */
    public function wait(Model $model): void
    {
        $model->setAttribute('status',0);
        $model->save();
    }
}

```


> 注册数据源

```php
\ManoCode\Approval\Library\DataSourcesManager::getInstance()->registerDataSources(new PurchaseDataSources());
```

#### 3. 手动发起审批操作（可选）不需要自动审批或 逻辑复杂时使用

```php

    /**
     * 推送采购审核
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function pushProcessApi(Request $request)
    {
        if (!($purchase = Purchase::query()->where(['id' => $request->input('id'), 'status' => 5])->first())) {
            return $this->response()->fail('采购订单不存在');
        }
        /**
         * 触发 自定义推送事件 时间内会自动修改状态为 待审核
         */
        \ManoCode\Approval\Library\DataSourcesManager::getInstance()->triggerEvent('push-process', $purchase);
        return $this->response()->success([], '提审成功');
    }

```
