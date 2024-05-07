<?php

namespace mano-code\Approval;

use Illuminate\Support\Facades\Event;
use Slowlyo\OwlAdmin\Extend\Extension;
use Slowlyo\OwlAdmin\Models\AdminMenu;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;
use mano-code\Approval\DataSources\NewsDataSources;
use mano-code\Approval\Library\DataSourcesManager;

class ApprovalServiceProvider extends ServiceProvider
{
    protected $menu = [
        [
            'parent'   => '',
            'title'    => '审批测试',
            'url'      => '/approval_demo',
            'url_type' => '1',
            'icon'     => 'fluent-mdl2:document-approval',
        ],
        [
            'parent'   => '审批测试',
            'title'    => '新闻发布',
            'url'      => '/news',
            'url_type' => '1',
            'icon'     => 'arcticons:bangkok-biznews',
        ],
    ];

    public function boot()
    {
        // 注册新闻数据源
        DataSourcesManager::getInstance()->registerDataSources(new NewsDataSources());
        /**
         * 监听所有模型事件
         */
        Event::listen('eloquent.*', function($event, $model){
            if(!DataSourcesManager::isBeginHook()){
                return ;
            }
            if(!preg_match('/eloquent\.(.*?):\s(.+)/',$event,$modelClass)){
                return ;
            }
            [,$eventStr,$modelStr] = $modelClass;
            [$model] = $model;
            DataSourcesManager::getInstance()->triggerEvent($eventStr,$model);
        });

        if (Extension::tableExists()) {
            $this->autoRegister();
            $this->init();
        }
    }
    public function register()
    {

    }


	public function settingForm()
	{
	    return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(true),
	    ]);
	}
}
