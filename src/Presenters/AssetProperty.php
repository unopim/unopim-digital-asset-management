<?php

namespace Webkul\DAM\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class AssetProperty implements HistoryPresenterInterface
{
    protected static $historyFieldNames = [
        'name'  => 'Property Name',
        'type'  => 'Property Type',
        'value' => 'Property Value',
    ];

    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        return [
            $fieldName => [
                'name' => static::$historyFieldNames[$fieldName],
                'old'  => $oldValues ?: '',
                'new'  => $newValues ?: '',
            ],
        ];
    }
}
