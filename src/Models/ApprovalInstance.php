<?php

namespace Uupt\Approval\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 审批实例
 */
class ApprovalInstance extends Model
{
    use SoftDeletes;

    protected $table = 'approval_instance';
    
}
