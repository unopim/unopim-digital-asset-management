<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Contracts\Asset;
use Webkul\DAM\DataGrids\Asset\PickerDataGrid;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Repositories\AssetRepository;

class AssetPickerController extends Controller
{
    /**
     * create controller object
     */
    public function __construct(protected AssetRepository $assetRepository) {}

    /**
     * Asset Picker Datagrid
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(PickerDataGrid::class)->toJson();
        }
    }

    /**
     * Fetch Assets through id
     */
    public function fetchAssets(): JsonResponse
    {
        $assetIds = request()->get('assetIds') ?? '';

        if (empty($assetIds)) {
            return response()->json([]);
        }

        if (is_string($assetIds)) {
            $assetIds = str_contains($assetIds, ',') ? explode(',', $assetIds) : [$assetIds];
        }

        $assets = $this->assetRepository->findWhereIn('id', $assetIds);

        $response = [];

        foreach ($assets as $asset) {
            $response[] = $this->formatAsset($asset);
        }

        return response()->json($response);
    }

    /**
     * Format the asset object
     */
    protected function formatAsset(Asset $asset): array
    {
        $assetId = $asset->id;

        $filePath = $asset->path;

        return [
            'id'                => $assetId,
            'url'               => route('admin.dam.file.thumbnail', ['path' => urlencode($filePath)]),
            'value'             => $assetId,
            'file_name'         => AssetHelper::getDisplayFileName($asset->file_name),
            'file_type'         => $asset->file_type,
            'storage_file_path' => $filePath,
        ];
    }
}
