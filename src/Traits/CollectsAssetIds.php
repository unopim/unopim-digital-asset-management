<?php

namespace Webkul\DAM\Traits;

trait CollectsAssetIds
{
    /**
     * Collect an asset field's ids from every scope the values carry.
     *
     * A single requested channel and locale is right for an admin request but wrong on a
     * queued import: there is no request, so those fall back to defaults that need not
     * match the channel or locale the import wrote. Reading the union of
     * `common`, `locale_specific`, `channel_specific` and `channel_locale_specific`
     * keeps the result correct whichever scope the values target.
     *
     * @return list<int|string>
     */
    protected function collectAssetIdsAcrossScopes(array $values, string $fieldCode): array
    {
        $collected = [];

        $harvest = function ($value) use (&$collected): void {
            if (in_array($value, [null, '', []], true)) {
                return;
            }

            if (! is_array($value)) {
                $value = str_contains((string) $value, ',')
                    ? explode(',', (string) $value)
                    : [$value];
            }

            foreach ($value as $assetId) {
                $assetId = trim((string) $assetId);

                if ($assetId !== '') {
                    $collected[$assetId] = $assetId;
                }
            }
        };

        $harvest($values['common'][$fieldCode] ?? null);

        foreach (($values['locale_specific'] ?? []) as $localeValues) {
            $harvest($localeValues[$fieldCode] ?? null);
        }

        foreach (($values['channel_specific'] ?? []) as $channelValues) {
            $harvest($channelValues[$fieldCode] ?? null);
        }

        foreach (($values['channel_locale_specific'] ?? []) as $channelValues) {
            foreach ((array) $channelValues as $localeValues) {
                $harvest($localeValues[$fieldCode] ?? null);
            }
        }

        return array_values($collected);
    }
}
