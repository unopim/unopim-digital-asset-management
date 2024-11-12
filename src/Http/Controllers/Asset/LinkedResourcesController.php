<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Asset\LinkedResourcesDataGrid;

class LinkedResourcesController extends Controller
{
    /**
     * Datagrid route
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(LinkedResourcesDataGrid::class)->toJson();
        }
    }
}
