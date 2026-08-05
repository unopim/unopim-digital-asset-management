<?php

namespace Webkul\DAM\Helpers\Importers\Concerns;

use Webkul\DAM\Support\AssetPathStorage;

/**
 * Turns the comma-separated asset paths carried by a data file into DAM asset ids.
 *
 * A path that resolves to nothing is reported rather than dropped: the value is left
 * unset and the job's error report names the path, so a missing asset is visible
 * instead of silently absent from the imported record.
 */
trait ResolvesAssetPaths
{
    public const ERROR_CODE_ASSET_NOT_FOUND = 'dam_asset_not_found';

    protected ?AssetPathStorage $assetPathStorage = null;

    /**
     * @return list<int> ids for the paths that exist, in the order given
     */
    protected function resolveAssetIds(string $rawValue): array
    {
        $paths = array_values(array_filter(
            array_map('trim', explode(',', $rawValue)),
            fn (string $path): bool => $path !== ''
        ));

        if ($paths === []) {
            return [];
        }

        $storage = $this->assetPathStorage();
        $storage->load($paths);

        $ids = [];

        foreach ($paths as $path) {
            $id = $storage->get($path);

            if ($id === null) {
                $this->reportMissingAsset($path);

                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Errors are keyed by path, so each unresolved asset is reported once however many
     * records reference it.
     */
    protected function reportMissingAsset(string $path): void
    {
        $this->errorHelper->addErrorMessage(
            self::ERROR_CODE_ASSET_NOT_FOUND,
            trans('dam::app.data-transfer.bundle.asset-not-found')
        );

        $this->errorHelper->addError(self::ERROR_CODE_ASSET_NOT_FOUND, null, $path);
    }

    protected function assetPathStorage(): AssetPathStorage
    {
        return $this->assetPathStorage ??= app(AssetPathStorage::class);
    }
}
