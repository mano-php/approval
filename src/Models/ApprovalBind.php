<?php

namespace mano-code\Approval\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 审批流关联
 */
class ApprovalBind extends Model
{
    use SoftDeletes;

    protected $table = 'approval_bind';
    
}
