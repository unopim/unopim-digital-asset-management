<?php

namespace Webkul\DAM\Traits;

trait CollectsAssetIds
{
    /**
     * Reads every scope because a queued import has no request channel or locale to narrow it to.
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
