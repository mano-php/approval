# 钉钉审批模块


### 在 app EventServiceProvider $listen 内新增监听

```php

    \UUPT\Corp\Events\DingNotify\BpmsTaskChangeEvent::class=>[
        \mano-code\Approval\Listeners\ApprovalEventHandler::class,
    ],
```
