<?php

namespace Webkul\DAM\Helpers\Importers\Concerns;

use Webkul\DAM\Support\AssetPathStorage;
use Webkul\DataTransfer\Helpers\Import;

trait ResolvesAssetPaths
{
    protected ?AssetPathStorage $assetPathStorage = null;

    /**
     * @return list<int> 
     */
    protected function resolveAssetIds(string $rawValue): array
    {
        $paths = $this->parseAssetPaths($rawValue);

        if ($paths === []) {
            return [];
        }

        $storage = $this->assetPathStorage();
        $storage->load($paths);

        $ids = [];

        foreach ($paths as $path) {
            $id = $storage->get($path);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  iterable<string>  $columns
     */
    protected function warnAboutMissingAssets(array $rowData, int $rowNumber, bool $firstValidation, string $identifier, iterable $columns): void
    {
        if (! $firstValidation || $this->import->action === Import::ACTION_DELETE) {
            return;
        }

        foreach ($columns as $column) {
            $paths = $this->parseAssetPaths((string) ($rowData[$column] ?? ''));

            if ($paths === []) {
                continue;
            }

            $storage = $this->assetPathStorage();
            $storage->load($paths);

            foreach ($paths as $path) {
                if ($storage->has($path)) {
                    continue;
                }

                $this->jobLogger?->warning(trans('dam::app.data-transfer.bundle.asset-not-found', [
                    'row'        => $rowNumber,
                    'identifier' => $identifier,
                    'column'     => $column,
                    'path'       => $path,
                ]));
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function parseAssetPaths(string $rawValue): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode(',', $rawValue)),
            fn (string $path): bool => $path !== ''
        ));
    }

    protected function assetPathStorage(): AssetPathStorage
    {
        return $this->assetPathStorage ??= resolve(AssetPathStorage::class);
    }
}
