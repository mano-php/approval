<?php

namespace ManoCode\Approval\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Slowlyo\OwlAdmin\Support\CodeGenerator\Generator;
use ManoCode\Approval\Models\ApprovalBind;
use ManoCode\Approval\Services\ApprovalBindService;
use ManoCode\MiniWechat\Models\Member;

/**
 * 审批流关联
 *
 * @property ApprovalBindService $service
 */
class ApprovalBindController extends AdminController
{
    public function getApprovalBind(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = ApprovalBind::query();
        if(strlen(strval($request->input('term')))>=1){
            $query->where(function($where) use($request){
                $where->where('name','like',"%{$request->input('term')}%")->orWhere('description','like',"%{$request->input('term')}%")->orWhere('table_name','like',"%{$request->input('term')}%");
            });
        }
        return response()->json([
            'options'=>$query->select([
                DB::raw('name as label'),
                DB::raw('id as value'),
            ])->get()
        ]);
    }
    public function getApprovalData(Request $request): \Illuminate\Http\JsonResponse
    {
        if(intval($request->input('bind_id'))<=0){
            return response()->json([
                'options'=>[]
            ]);
        }
        $table = ApprovalBind::query()->where('id',$request->input('bind_id'))->value('table_name');
        $query = DB::table(explode('-',$table)[0]);
        if(strlen(strval($request->input('term')))>=1){
            $query->where(function($where) use($request){
                $where->where('id','like',"%{$request->input('term')}%");
            });
        }
        $options = [];
        foreach ($query->get() as $item){
            $options[] = [
                'label'=>json_encode($item),
                'value'=>$item->id
            ];
        }

        return response()->json([
            'options'=>$options
        ]);
    }
    protected string $serviceName = ApprovalBindService::class;

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
				amis()->TableColumn('name', '名称'),
				amis()->TableColumn('description', '描述'),
				amis()->TableColumn('table_name', '表名'),
				amis()->TableColumn('process_code', '表单ID'),
				amis()->TableColumn('created_at', __('admin.created_at'))->set('type', 'datetime')->sortable(),
//				amis()->TableColumn('updated_at', __('admin.updated_at'))->set('type', 'datetime')->sortable(),
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
            $this->rowEditButton($dialog, $dialogSize),
            $this->rowDeleteButton(),
        ]);
    }

    /**
     * 行编辑按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function rowEditButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->form(true)
                ->api($this->getUpdatePath())
                ->initApi($this->getEditGetDataPath())
                ->redirect('')
                ->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.edit'))->body($form)->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getEditPath());
        }

        return $button->label(__('admin.edit'))->icon('fa-regular fa-pen-to-square')->level('link');
    }

    /**
     * 行详情按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function rowShowButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->detail()
                ->api($this->getUpdatePath())
                ->initApi($this->getEditGetDataPath())
                ->redirect('')
                ->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.show'))->body($form)->size($dialogSize)->actions([])
            );
        } else {
            $button = amis()->LinkAction()->link($this->getShowPath());
        }
        return $button->label(__('admin.show'))->icon('fa-regular fa-eye')->level('link');
    }
    /**
     * 字段表单
     */
    public function columnForm(bool $isShow = false)
    {
        return amis()->Card()->body([
            amis()->Alert()
                ->body("如果字段名存在 status、state 则不会同步到钉钉!")
                ->level('warning')
                ->showCloseButton()
                ->showIcon(),
            amis()->SubFormControl('columns', false)
                ->static($isShow)
                ->multiple()
                ->btnLabel('${"<div class=\'column-name\'>"+ name + "</div><div class=\'text-success\'>" + type +"</div><div class=\'item-comment\'>"+ comment +"</div>"}')
                ->minLength(1)
                ->draggable(!$isShow)
                ->addable(false)
                ->removable(!$isShow)
                ->itemClassName('custom-subform-item')
                ->form(
                    $isShow?'':
                    amis()->FormControl()
                        ->set('title', '字段编辑')
                        ->size('lg')
                        ->id('column_form')
                        ->body([
                            amis()->TextControl('name', __('admin.code_generators.column_name'))
                                ->required()->disabled(),
                            amis()->SelectControl('type', __('admin.code_generators.type'))
                                ->options(Generator::make()->availableFieldTypes())
                                ->searchable()
                                ->value('string')
                                ->required()->disabled(),
                            amis()->TextControl('comment', __('admin.code_generators.comment'))->value(),
                            amis()->SelectControl('columnType', '组件')->options([
                                [
                                    'label'=>'文本',
                                    'value'=>'TextField'
                                ],
                                [
                                    'label'=>'多行文本',
                                    'value'=>'TextareaField'
                                ],
                                [
                                    'label'=>'数字输入框',
                                    'value'=>'NumberField'
                                ],
                            ])->required(),
                        ])
                ),
        ]);
    }

    public function form($isEdit = false,bool $isShow = false): Form
    {
        $databaseColumns = Generator::make()->getDatabaseColumns();
        return $this->baseForm()->wrapWithPanel(false)->labelWidth(150)->mode('horizontal')->resetAfterSubmit()->data([
            'table_info'         => $databaseColumns,
        ])->id('bind-form')->tabs([
            amis()->Tab()->title(__('admin.code_generators.base_info'))->body([
                amis()->SelectControl('table_name', '数据表')
                    ->searchable()
                    ->clearable()
                    ->static($isShow)
                    ->selectMode('group')
                    ->options(
                        $databaseColumns->map(function ($item, $index) {
                            return [
                                'label'    => $index,
                                'children' => $item->keys()->map(function ($item) use ($index) {
                                    return ['value' => $item . '-' . $index, 'label' => $item];
                                }),
                            ];
                        })->values()
                    )
                    ->onEvent([
                        'change'=>[
                            'actions'=>[
                                [
                                    'actionType'  => 'setValue',
                                    'componentId' => 'bind-form',
                                    'args'        => [
                                        'value' => [
                                            'columns'     => '${table_info[SPLIT(event.data.value, "-")[1]][SPLIT(event.data.value, "-")[0]]}',
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ])
                ,
                amis()->TextControl('name', '名称')->static($isShow),
                amis()->TextareaControl('url', '审核链接')->required()->static($isShow),
                amis()->TextareaControl('description', '备注')->static($isShow),
            ]),
            // 用于取舍字段
            amis()->Tab()->title('字段设置')->body($this->columnForm($isShow)),
        ]);
    }

    public function detail(): Form
    {
        $databaseColumns = Generator::make()->getDatabaseColumns();
        return $this->baseForm()->wrapWithPanel(false)->labelWidth(150)->mode('horizontal')->resetAfterSubmit()->data([
            'table_info'         => $databaseColumns,
        ])->id('bind-form')->tabs([
            amis()->Tab()->title(__('admin.code_generators.base_info'))->body([
                amis()->SelectControl('table_name', '数据表')
                    ->static()
                    ->searchable()
                    ->clearable()
                    ->selectMode('group')
                    ->options(
                        $databaseColumns->map(function ($item, $index) {
                            return [
                                'label'    => $index,
                                'children' => $item->keys()->map(function ($item) use ($index) {
                                    return ['value' => $item . '-' . $index, 'label' => $item];
                                }),
                            ];
                        })->values()
                    )
                    ->onEvent([
                        'change'=>[
                            'actions'=>[
                                [
                                    'actionType'  => 'setValue',
                                    'componentId' => 'bind-form',
                                    'args'        => [
                                        'value' => [
                                            'columns'     => '${table_info[SPLIT(event.data.value, "-")[1]][SPLIT(event.data.value, "-")[0]]}',
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ])
                ,
                amis()->TextControl('name', '名称')->static(),
                amis()->TextareaControl('url', '审核链接')->required()->static(),
                amis()->TextareaControl('description', '备注')->static(),
            ]),
            // 用于取舍字段
            amis()->Tab()->title('字段设置')->body($this->columnForm(true)),
        ]);
    }
}
