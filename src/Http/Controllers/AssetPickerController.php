<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Contracts\Asset;
use Webkul\DAM\DataGrids\Asset\PickerDataGrid;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

class AssetPickerController extends Controller
{
    public function __construct(
        protected AssetRepository $assetRepository,
        protected DirectoryPermissionService $permissionService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return app(PickerDataGrid::class)->toJson();
        }
    }

    public function fetchAssets(): JsonResponse
    {
        $assetIds = request()->get('assetIds') ?? '';

        if (empty($assetIds)) {
            return response()->json([]);
        }

        if (is_string($assetIds)) {
            $assetIds = str_contains($assetIds, ',') ? explode(',', $assetIds) : [$assetIds];
        }

        $query = $this->assetRepository->getModel()->newQuery()->whereIn('id', $assetIds);

        if (! $this->permissionService->bypass()) {
            $grantedDirectoryIds = $this->permissionService->directlyGrantedIds();

            $query->whereHas(
                'directories',
                fn ($directories) => $directories->whereIn('dam_directories.id', $grantedDirectoryIds)
            );
        }

        $assets = $query->get();

        $response = [];

        foreach ($assets as $asset) {
            $response[] = $this->formatAsset($asset);
        }

        return response()->json($response);
    }

    protected function formatAsset(Asset $asset): array
    {
        $assetId = $asset->id;

        $filePath = $asset->path;

        return [
            'id'                => $assetId,
            'url'               => AssetHelper::getThumbnailUrl($filePath),
            'value'             => $assetId,
            'file_name'         => AssetHelper::getDisplayFileName($asset->file_name),
            'file_type'         => $asset->file_type,
            'storage_file_path' => $filePath,
        ];
    }
}
