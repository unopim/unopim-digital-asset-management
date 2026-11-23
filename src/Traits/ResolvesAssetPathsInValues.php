<?php

namespace Webkul\DAM\Traits;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DAM\Repositories\AssetRepository;

/**
 * Swaps asset-attribute ids for their stored paths in product values and webhook diffs.
 */
trait ResolvesAssetPathsInValues
{
    /** @var array<int, string>|null */
    protected ?array $assetAttributeCodes = null;

    /**
     * Swap asset ids for paths across every scope of a product `values` array.
     */
    protected function resolveAssetPathsInValues(array &$values): void
    {
        if ($this->assetAttributeCodes() === []) {
            return;
        }

        if (isset($values['common']) && is_array($values['common'])) {
            $this->resolveAssetPathsInFields($values['common']);
        }

        foreach (['locale_specific', 'channel_specific'] as $scope) {
            if (! isset($values[$scope]) || ! is_array($values[$scope])) {
                continue;
            }

            foreach ($values[$scope] as &$fields) {
                if (is_array($fields)) {
                    $this->resolveAssetPathsInFields($fields);
                }
            }

            unset($fields);
        }

        if (isset($values['channel_locale_specific']) && is_array($values['channel_locale_specific'])) {
            foreach ($values['channel_locale_specific'] as &$locales) {
                if (! is_array($locales)) {
                    continue;
                }

                foreach ($locales as &$fields) {
                    if (is_array($fields)) {
                        $this->resolveAssetPathsInFields($fields);
                    }
                }

                unset($fields);
            }

            unset($locales);
        }
    }

    /**
     * Resolve the asset values in one field group.
     */
    protected function resolveAssetPathsInFields(array &$fields): void
    {
        foreach ($this->assetAttributeCodes() as $code) {
            if (! array_key_exists($code, $fields)) {
                continue;
            }

            $fields[$code] = $this->resolveAssetValueToPaths($fields[$code]);
        }
    }

    /**
     * Swap asset ids for paths inside a product webhook `changes` diff.
     */
    protected function resolveAssetPathsInChanges(array &$changes): void
    {
        if ($this->assetAttributeCodes() === []) {
            return;
        }

        foreach (['added', 'removed', 'changed'] as $operation) {
            if (! empty($changes[$operation]) && is_array($changes[$operation])) {
                $this->resolveAssetPathsInChangeNode($changes[$operation], $operation === 'changed');
            }
        }
    }

    /**
     * Recurse a diff bucket, resolving asset keys (both sides of a changed pair).
     */
    protected function resolveAssetPathsInChangeNode(array &$node, bool $isChanged): void
    {
        $assetCodes = $this->assetAttributeCodes();

        foreach ($node as $key => &$value) {
            if (in_array($key, $assetCodes, true)) {
                if ($isChanged && is_array($value) && array_key_exists('new', $value)) {
                    $value['old'] = $this->resolveAssetValueToPaths($value['old'] ?? null);
                    $value['new'] = $this->resolveAssetValueToPaths($value['new'] ?? null);
                } else {
                    $value = $this->resolveAssetValueToPaths($value);
                }
            } elseif (is_array($value)) {
                $this->resolveAssetPathsInChangeNode($value, $isChanged);
            }
        }

        unset($value);
    }

    /**
     * Map an id / comma list / array of ids to a comma-separated path string.
     * An unmatched id is kept as-is so a stale reference is never blanked.
     */
    protected function resolveAssetValueToPaths(mixed $value): mixed
    {
        if (in_array($value, [null, '', []], true)) {
            return $value;
        }

        $ids = is_array($value)
            ? array_values(array_filter(array_map(fn ($id): string => trim((string) $id), $value), 'strlen'))
            : array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));

        if ($ids === []) {
            return $value;
        }

        $assetsById = $this->assetPathRepository()
            ->findWhereIn('id', $ids)
            ->keyBy(fn ($asset) => (string) $asset->id);

        $resolved = array_map(
            fn (string $id): string => $assetsById->get($id)?->path ?: $id,
            $ids
        );

        return implode(', ', $resolved);
    }

    /**
     * @return array<int, string>
     */
    protected function assetAttributeCodes(): array
    {
        return $this->assetAttributeCodes ??= resolve(AttributeRepository::class)
            ->getModel()
            ->newQuery()
            ->where('type', 'asset')
            ->pluck('code')
            ->all();
    }

    protected function assetPathRepository(): AssetRepository
    {
        return resolve(AssetRepository::class);
    }
}
