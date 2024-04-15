<?php

namespace Uupt\Approval\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 数据流关联
 */
class ProcessCodeBind extends Model
{
    use SoftDeletes;

    protected $table = 'process_code_bind';

}
