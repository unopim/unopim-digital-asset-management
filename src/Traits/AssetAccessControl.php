<?php

namespace Webkul\DAM\Traits;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\DirectoryPermissionService;

/**
 * Per-asset directory ACL gate. Use in controllers that operate on a specific
 * asset id — calls layer on top of the existing `bouncer()` route-level ACL,
 * so a request must satisfy BOTH the ACL key (admin middleware) AND the
 * directory grant (this trait) to pass.
 *
 * Bypass roles (`permission_type=all`, anonymous, API guard) skip the
 * directory check transparently — the `bouncer()` ACL still applies as it
 * does today.
 */
trait AssetAccessControl
{
    protected function damPermissionService(): DirectoryPermissionService
    {
        return app(DirectoryPermissionService::class);
    }

    /**
     * Resolve an asset's containing directory id (asset → dam_asset_directory pivot).
     */
    protected function damAssetDirectoryId(?Asset $asset): ?int
    {
        if (! $asset) {
            return null;
        }

        // Read the pivot directly rather than $asset->directories()->value(...):
        // the directories() relation resolves Directory models through the
        // nested-set query builder, and resolving a single column that way blows
        // up for non-bypass (directory-scoped) users.
        $dirId = DB::table('dam_asset_directory')
            ->where('asset_id', $asset->id)
            ->value('directory_id');

        return $dirId ? (int) $dirId : null;
    }

    /**
     * Returns true when the current admin can act on the given asset id based
     * on its containing directory grant. Bypass roles always pass.
     */
    protected function damCanAccessAsset(int $assetId): bool
    {
        $service = $this->damPermissionService();

        if ($service->bypass()) {
            return true;
        }

        $dirId = $this->damAssetDirectoryId(Asset::find($assetId));

        if ($dirId === null) {
            return false;
        }

        return $service->canAccess($dirId);
    }

    /**
     * Abort 403 if the current admin cannot act on the given asset id.
     */
    protected function damAuthorizeAsset(int $assetId): void
    {
        if (! $this->damCanAccessAsset($assetId)) {
            abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }
    }
}
