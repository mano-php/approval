<?php

namespace ManoCode\Approval\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use ManoCode\Approval\Services\ApprovalInstanceService;
use ManoCode\Corp\Library\DingHttp;

/**
 * 审批实例
 *
 * @property ApprovalInstanceService $service
 */
class ApprovalInstanceController extends AdminController
{
    protected string $serviceName = ApprovalInstanceService::class;
    public function getTableColumn(Request $request)
    {
        if(!(strlen($request->input('table'))>= 1 && count(explode('-',$request->input('table')))>=1)){
            return $this->response()->success();
        }
        $tableName = explode('-',$request->input('table'))[0];
        $columns = [];
        foreach (DB::select("SHOW FULL COLUMNS FROM {$tableName}") as $key=>$column) {
            $columns[$key]['columnName'] = $column->Field;
            $columns[$key]['columnComment'] = $column->Comment ?: $column->Field;
            $columns[$key]['isNullable'] = $column->Null == 'YES' ? 'Yes' : 'No';
            $columns[$key]['columnType'] = $column->Type;
            $columns[$key]['component'] = 'TextField';
        }
        return $this->response()->success($columns);
    }
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
                amis()->SelectControl('approval_bind_id', '表单')->source('/approval/approval_bind')->static(),
                admin_corp_amis()->employeeForm('originator_user_id', '发起人')->static(),
				amis()->TableColumn('title', '标题'),
				amis()->TableColumn('process_instance_id', '实例ID'),
				amis()->TableColumn('url', '详情链接'),
				amis()->TableColumn('created_at', __('admin.created_at'))->set('type', 'datetime')->sortable(),
				amis()->TableColumn('updated_at', __('admin.updated_at'))->set('type', 'datetime')->sortable(),
                $this->rowActions(true)
            ]);

        return $this->baseList($crud);
    }

    /**
     * 操作列
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Operation
     */
    protected function rowActions(bool|array $dialog = false, string $dialogSize = '')
    {
        if (is_array($dialog)) {
            return amis()->Operation()->label(__('admin.actions'))->buttons($dialog);
        }

        return amis()->Operation()->label(__('admin.actions'))->buttons([
            $this->rowShowButton($dialog, $dialogSize),
//            $this->rowEditButton($dialog, $dialogSize),
            $this->rowDeleteButton(),
        ]);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->SelectControl('approval_bind_id', '表单')->source('/approval/approval_bind')->required()->clearable()->searchable(),
            amis()->SelectControl('approval_bind_data', '数据')->source('/approval/approval_data?bind_id=${approval_bind_id}')->required()->clearable()->searchable(),
            admin_corp_amis()->employeeForm('originator_user_id', '发起人')->multiple(false)->required(),
			amis()->TextControl('title', '标题')->required(),
//			amis()->TextControl('process_instance_id', '实例ID'),
			amis()->TextControl('url', '详情链接')->required(),
        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([
            amis()->TextControl('id', 'ID')->static(),
            amis()->SelectControl('status','审批进度')->placeholder('审批中')->options([
                [
                    'label'=>'审批中',
                    'value'=>'RUNNING'
                ],
                [
                    'label'=>'已撤销',
                    'value'=>'TERMINATED'
                ],
                [
                    'label'=>'审批完成',
                    'value'=>'COMPLETED'
                ],
            ])->static(),
            amis()->SelectControl('result','审批结果')->placeholder('审批中')->options([
                [
                    'label'=>'同意',
                    'value'=>'agree'
                ],
                [
                    'label'=>'拒绝',
                    'value'=>'refuse'
                ],
            ])->static(),
            amis()->SelectControl('approval_bind_id', '表单')->source('/approval/approval_bind')->static(),
            admin_corp_amis()->employeeForm('originator_user_id', '发起人')->static(),
			amis()->TextControl('title', '标题')->static(),
			amis()->TextControl('process_instance_id', '实例ID')->static(),
			amis()->TextControl('url', '详情链接')->static(),
			amis()->TextControl('created_at', __('admin.created_at'))->static(),
			amis()->TextControl('updated_at', __('admin.updated_at'))->static()
        ]);
    }
}
