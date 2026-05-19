<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Asset\LinkedResourcesDataGrid;
use Webkul\DAM\Traits\AssetAccessControl;

class LinkedResourcesController extends Controller
{
    use AssetAccessControl;

    /**
     * Datagrid route
     */
    public function index()
    {
        $assetId = request()->get('dam_asset_id');

        if ($assetId) {
            $this->damAuthorizeAsset((int) $assetId);
        }

        if (request()->ajax()) {
            return app(LinkedResourcesDataGrid::class)->toJson();
        }
    }
}
