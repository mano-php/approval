<?php

namespace mano-code\Approval\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use mano-code\Approval\Library\DataSourcesManager;
use UUPT\Corp\Events\DingNotify\BpmsTaskChangeEvent;

class ApprovalEventHandler implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(BpmsTaskChangeEvent $event): void
    {
        $eventData = collect($event->getData());
        try {
            DataSourcesManager::getInstance()->notify(strval($eventData->get('processCode')), strval($eventData->get('processInstanceId')), strval($eventData->get('type')), strval($eventData->get('result')));
        } catch (\Throwable $throwable) {
            Log::error('审批流程回调处理失败,' . serialize([
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => $throwable->getTraceAsString(),
                ]));
        }
    }
}
