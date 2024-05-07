<?php

namespace mano-code\Approval\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 新闻发布
 */
class News extends Model
{
    use SoftDeletes;
}
