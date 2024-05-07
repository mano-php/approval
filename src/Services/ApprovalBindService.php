<?php

namespace ManoCode\Approval\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ManoCode\Approval\Models\ApprovalBind;
use Slowlyo\OwlAdmin\Services\AdminService;
use UUPT\Corp\Library\DingHttp;

/**
 * 审批流关联
 *
 * @method ApprovalBind getModel()
 * @method ApprovalBind|\Illuminate\Database\Query\Builder query()
 */
class ApprovalBindService extends AdminService
{
    protected string $modelName = ApprovalBind::class;

    protected function mapFieldTypeToControl($fieldType): string
    {
        $fieldType = strtolower($fieldType);

        switch ($fieldType) {
            case 'integer':
            case 'unsignedinteger':
            case 'tinyinteger':
            case 'unsignedtinyinteger':
            case 'smallinteger':
            case 'unsignedsmallinteger':
            case 'mediuminteger':
            case 'unsignedmediuminteger':
            case 'biginteger':
            case 'unsignedbiginteger':
                return 'NumberField';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'DDDateField';
            case 'enum':
                return 'DDSelectField';
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return 'TextareaField';
            case 'string':
            case 'char':
                return 'TextField';
            case 'json':
                return 'TextField'; // Or you can define a custom control for JSON data
            case 'binary':
                return 'TextField'; // Binary data might need special handling, but for simplicity, use TextField
            case 'float':
            case 'double':
            case 'decimal':
                return 'MoneyField'; // Or you can define a custom control for handling money
            default:
                return 'TextField'; // Default to TextField for unmatched types
        }
    }

    /**
     * @param $data
     * @param $primaryKey
     * @return true
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saving(&$data, $primaryKey = '')
    {
//        $columns = [];
        $formComponents = [];
        foreach ($data['columns'] as $key=>$column) {
            if(in_array($column['name'],['status','state'])){
                continue ;
            }
            $formComponents[] = [
                'componentType'=>(isset($column['columnType']) && strlen($column['columnType'])>=1)?$column['columnType']:'TextField',
                'props'=>[
                    'componentId'=>$column['name'],
                    'label'=>$column['comment'],
                    'required'=>!$column['nullable'], // 是否必须
                    'disabled'=>false,// 是否可编辑
                ]
            ];
        }
        $data['columns'] = json_encode($data['columns']);

        $requestData = [
            'name'=>$data['name'],
            'description'=>$data['description'],
            'formComponents'=>$formComponents,
            'templateConfig'=>[
                'disableStopProcessButton'=>true,// 管理列表页是否禁用停用按钮。
                'hidden'=>false,// 是否全局隐藏流程模板入口。
                'disableDeleteProcess'=>true,// 是否禁用模板删除按钮。
                'disableFormEdit'=>false,// 是否禁止表单编辑功能。
                'disableResubmit'=>false,// 是否禁用详情页再次发起按钮。
                'disableHomepage'=>false,// 是否在首页隐藏模板。
            ],
        ];
        // 更新模板
        if(isset($data['process_code']) && strlen($data['process_code'])>=1){
            $requestData['processCode'] = $data['process_code'];
        }
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/forms',$requestData);

        if(isset($response['result']['processCode']) && strlen(strval($response['result']['processCode']))>=1){
            $data['process_code'] = strval($response['result']['processCode']);
        }else{
            dd($response);
        }
    }

    /**
     * 编辑 获取数据
     *
     * @param $id
     *
     * @return Model|\Illuminate\Database\Eloquent\Collection|Builder|array|null
     */
    public function getEditData($id)
    {
        $model = $this->getModel();

        $hidden = collect([$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()])
            ->filter(fn($item) => $item !== null)
            ->toArray();

        $query = $this->query();

        $this->addRelations($query, 'edit');
        $detail = $query->find($id)->makeHidden($hidden);
        $detail['columns'] = json_decode($detail['columns'],true);
        return $detail;
    }
    /**
     * 详情 获取数据
     *
     * @param $id
     *
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function getDetail($id)
    {
        $query = $this->query();

        $this->addRelations($query, 'detail');

        $detail = $query->find($id);
        $detail['columns'] = json_decode($detail['columns'],true);
        return $detail;
    }
    public function delete($ids)
    {
        foreach ($this->query()->whereIn($this->primaryKey(), explode(',', $ids))->get() as $item){
            if(strlen(strval($item->process_code))>=1){
                // 删除
                try{
                    DingHttp::delete("https://api.dingtalk.com/v1.0/workflow/processCentres/schemas?processCode={$item->process_code}&cleanRunningTask=true");
                }catch (\Throwable $throwable){
                    // 删除失败
                    continue;
                }
            }
            $item->delete();
            $this->deleted($ids);
        }
//        $result = $this->query()->whereIn($this->primaryKey(), explode(',', $ids))->delete();

//        if ($result) {
//            $this->deleted($ids);
//        }

        return true;
    }
}
