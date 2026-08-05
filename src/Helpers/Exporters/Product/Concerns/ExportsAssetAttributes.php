<?php

namespace Webkul\DAM\Helpers\Exporters\Product\Concerns;

use Webkul\DAM\Helpers\Exporters\Concerns\CopiesDamMedia;
use Webkul\DAM\Providers\EventServiceProvider;

trait ExportsAssetAttributes
{
    use CopiesDamMedia;

    protected function setAttributesValues(array $values, mixed $filePath, ?string $locale = null): array
    {
        $attributeValues = parent::setAttributesValues($values, $filePath, $locale);

        $filters = $this->getFilters();
        $withMedia = (bool) ($filters['with_media'] ?? false);
        $mediaSourceType = $filters['media_source_type'] ?? 'zip';

        foreach ($this->attributeMeta as $meta) {
            if (($meta['type'] ?? null) !== EventServiceProvider::ASSET_ATTRIBUTE_TYPE) {
                continue;
            }

            $code = $meta['code'];

            if (! $this->isAttributeValueExported($code)) {
                continue;
            }

            $paths = $this->resolveAssetPaths($values[$code] ?? null);

            if (empty($paths)) {
                continue;
            }

            if ($withMedia && $mediaSourceType === 'url') {
                $attributeValues[$code] = implode(', ', array_map(
                    fn ($path) => $this->makePublicUrlMedia($path, true),
                    $paths
                ));

                continue;
            }

            $attributeValues[$code] = implode(', ', $paths);

            if ($withMedia) {
                foreach ($paths as $path) {
                    $this->copyMedia($path, $filePath->getTemporaryPath().'/'.$path, true);
                }
            }
        }

        return $attributeValues;
    }

    protected function resolveAssetPaths(mixed $rawValue): array
    {
        if (empty($rawValue)) {
            return [];
        }

        $ids = is_array($rawValue)
            ? $rawValue
            : array_filter(array_map('trim', explode(',', (string) $rawValue)), 'strlen');

        if (empty($ids)) {
            return [];
        }

        return $this->assetRepository->findWhereIn('id', $ids)->pluck('path')->filter()->values()->all();
    }
}
