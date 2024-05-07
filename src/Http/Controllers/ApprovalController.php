<?php

namespace ManoCode\Approval\Http\Controllers;

use Slowlyo\OwlAdmin\Controllers\AdminController;

class ApprovalController extends AdminController
{
    public function index()
    {
        $page = $this->basePage()->body('Approval Extension.');

        return $this->response()->success($page);
    }
}
