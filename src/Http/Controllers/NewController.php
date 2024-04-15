<?php

namespace Uupt\Approval\Http\Controllers;

use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Uupt\Approval\Services\NewsService;

/**
 * 新闻发布
 *
 * @property NewsService $service
 */
class NewController extends AdminController
{
    protected string $serviceName = NewsService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
			->headerToolbar([
				$this->createButton(true),
				...$this->baseHeaderToolBar()
			])
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable(),
				amis()->TableColumn('title', '标题'),
				amis()->TableColumn('desc', '描述'),
                amis()->TagControl('status_name','状态')->color('${status==2?"success":(status==3?"error":"active")}')->displayMode('status')->type('tag')->static(),
				amis()->TableColumn('created_at', __('admin.created_at'))->set('type', 'datetime')->sortable(),
				amis()->TableColumn('updated_at', __('admin.updated_at'))->set('type', 'datetime')->sortable(),
                $this->rowActions(true)
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->HiddenControl('id', 'ID'),
            amis()->TextControl('title', '标题'),
			amis()->TextareaControl('desc', '描述'),
			amis()->TextareaControl('content', '新闻内容'),
        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([
            amis()->TextControl('id', 'ID')->static(),
			amis()->TextControl('title', '标题')->static(),
			amis()->TextControl('desc', '描述')->static(),
			amis()->TextControl('content', '新闻内容')->static(),
			amis()->SelectControl('status', '状态')->options(json_decode('[ 	{ 		"label":"待审核", 		"value":1 	}, 	{ 		"label":"已通过", 		"value":2 	}, 	{ 		"label":"已拒绝", 		"value":3 	}, ]',true))->static(),
			amis()->TextControl('created_at', __('admin.created_at'))->static(),
			amis()->TextControl('updated_at', __('admin.updated_at'))->static()
        ]);
    }
}
