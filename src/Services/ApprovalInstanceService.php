<?php

namespace ManoCode\Approval\Services;

use Illuminate\Support\Facades\DB;
use ManoCode\Approval\Models\ApprovalBind;
use ManoCode\Approval\Models\ApprovalInstance;
use Slowlyo\OwlAdmin\Services\AdminService;
use ManoCode\Corp\Library\DingHttp;
use ManoCode\Corp\Models\Employee;

/**
 * 审批实例
 *
 * @method ApprovalInstance getModel()
 * @method ApprovalInstance|\Illuminate\Database\Query\Builder query()
 */
class ApprovalInstanceService extends AdminService
{
    protected string $modelName = ApprovalInstance::class;

    /**
     * @param $data
     * @param $primaryKey
     * @return true
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saving(&$data, $primaryKey = '')
    {
        // 查询模板ID
        $approval_bind = ApprovalBind::query()->where('id',$data['approval_bind_id'])->first();
        // 查询发起人钉钉ID
        $employee = Employee::query()->where('id',intval($data['originator_user_id']))->first();
//        $connectionName = explode('-',$approval_bind['table_name'])[1];
        $tableName = explode('-',$approval_bind['table_name'])[0];
        $columns = [];

        $formComponentValueList = [];
        foreach (DB::select("SHOW FULL COLUMNS FROM $tableName") as $key=>$column) {
            $columns[$key]['columnName'] = $column->Field;
            $columns[$key]['columnComment'] = $column->Comment ?: $column->Field;
            $columns[$key]['isNullable'] = $column->Null == 'YES' ? 'Yes' : 'No';
            $columns[$key]['columnType'] = $column->Type;
            $value = DB::table($tableName)->where('id',$data['approval_bind_data'])->value($column->Field);
            $formComponentValueList[] = [
                'name'=>$columns[$key]['columnComment'],
//                'componentType'=>'TextField',
                'value'=> strval(strlen($value)>=1?$value:''),
            ];
        }
        $requestData = [
            'processCode'=>$approval_bind['process_code'],
            'originatorUserId'=>$employee['dingtalk_id'],
            'formComponentValues'=>$formComponentValueList,
            'title'=>$data['title'],
            'url'=>$data['url'],
            'deptId'=>'-1'
        ];
        /**
         * 发起流程
         */
        $response = DingHttp::post('https://api.dingtalk.com/v1.0/workflow/processInstances',$requestData);
        /**
         * 存储实例ID
         */
        if(isset($response['instanceId']) && strlen(strval($response['instanceId']))>=1){
            $data['process_instance_id'] = strval($response['instanceId']);
        }else{
            dump($requestData);
            dd($response);
        }
    }
    public function getDetail($id)
    {

        $query = $this->query();

        $this->addRelations($query, 'detail');
        $detail = $query->find($id);
        $response = DingHttp::get('https://api.dingtalk.com/v1.0/workflow/processInstances',[
            'processInstanceId'=>$detail['process_instance_id']
        ]);
//        $data = [
//            'status'=>$response['result']['status'], // 审批状态 。RUNNING：审批中,TERMINATED：已撤销,COMPLETED：审批完成
//            'result'=>$response['result']['result'], // 审批结果 agree：同意，refuse：拒绝
//        ];
        $detail['status'] = $response['result']['status'];
        $detail['result'] = $response['result']['result'];
        return $detail;
    }
}
