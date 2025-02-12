<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class LinkedResourcesController extends Controller
{
    public function __construct(
        protected AssetResourceMappingRepository $assetResourceMappingRepository,
    ) {}

    public function getLinkedResource(int $id)
    {
        $resource = $this->assetResourceMappingRepository->find($id);

        if (! $resource) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.linked-resources.not-found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.linked-resources.found-success'),
            'data'    => $resource,
        ], 200);
    }
}
