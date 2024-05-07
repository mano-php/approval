<?php

namespace mano-code\Approval\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use mano-code\Approval\Models\News;
use Slowlyo\OwlAdmin\Services\AdminService;

/**
 * 新闻发布
 *
 * @method News getModel()
 * @method News|\Illuminate\Database\Query\Builder query()
 */
class NewsService extends AdminService
{
    protected string $modelName = News::class;
    public function list()
    {
        $query = $this->listQuery();

        $list  = $query->paginate(request()->input('perPage', 20));
        $items = $list->items();
        $total = $list->total();
        foreach ($items as $key=>$item){
            $items[$key]['status_name'] = [1=>'待审核',2=>'已通过',3=>'已拒绝'][$item['status']];
        }

        return compact('items', 'total');
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

        return $query->find($id)->makeHidden($hidden);
    }
}
