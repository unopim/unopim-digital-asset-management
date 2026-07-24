<?php

namespace Webkul\DAM\Traits;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\DirectoryPermissionService;

trait AssetAccessControl
{
    protected function damPermissionService(): DirectoryPermissionService
    {
        return app(DirectoryPermissionService::class);
    }

    protected function damAssetDirectoryId(?Asset $asset): ?int
    {
        if (! $asset) {
            return null;
        }

        $dirId = DB::table('dam_asset_directory')
            ->where('asset_id', $asset->id)
            ->value('directory_id');

        return $dirId ? (int) $dirId : null;
    }

    protected function damCanAccessAsset(int $assetId): bool
    {
        try {
            $service = $this->damPermissionService();

            if ($service->bypass()) {
                return true;
            }

            $dirId = $this->damAssetDirectoryId(Asset::find($assetId));

            if ($dirId === null) {
                return false;
            }

            return $service->canAccess($dirId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function damAuthorizeAsset(int $assetId): void
    {
        if (! $this->damCanAccessAsset($assetId)) {
            abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }
    }
}
