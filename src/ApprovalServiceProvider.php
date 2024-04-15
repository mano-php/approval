<?php

namespace Uupt\Approval;

use Illuminate\Support\Facades\Event;
use Slowlyo\OwlAdmin\Extend\Extension;
use Slowlyo\OwlAdmin\Models\AdminMenu;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;
use Uupt\Approval\DataSources\NewsDataSources;
use Uupt\Approval\Library\DataSourcesManager;

class ApprovalServiceProvider extends ServiceProvider
{
    public function install()
    {
        parent::install();
        $this->installMenu();
    }
    protected function installMenu(): void
    {
        $demo_menu_id = AdminMenu::query()->insertGetId([
            'parent_id' => 0,
            'order' => 0,
            'title' => '审批测试',
            'icon' => 'fluent-mdl2:document-approval',
            'url' => '/approval_demo',
            'url_type' => 1,
            'visible' => 1,
            'is_home' => 0,
            'keep_alive' => 0,
            'iframe_url' => NULL,
            'component' => 'amis',
            'is_full' => 0,
            'extension' => NULL,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        AdminMenu::query()->insert([
            [
                'parent_id' => $demo_menu_id,
                'order' => 100,
                'title' => '新闻发布',
                'icon' => 'arcticons:bangkok-biznews',
                'url' => '/news',
                'url_type' => 1,
                'visible' => 1,
                'is_home' => 0,
                'keep_alive' => 0,
                'iframe_url' => NULL,
                'component' => NULL,
                'is_full' => 0,
                'extension' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

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
