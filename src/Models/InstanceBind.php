<?php

namespace ManoCode\Approval\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 实例关联
 */
class InstanceBind extends Model
{
    use SoftDeletes;

    protected $table = 'instance_bind';

}
