<?php

namespace Webkul\DAM\Presenters;

use Webkul\Core\Models\Locale;
use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class AssetProperty implements HistoryPresenterInterface
{
    protected static $historyFieldNames = [
        'name'     => 'Property Name',
        'type'     => 'Property Type',
        'value'    => 'Property Value',
        'language' => 'Property Language',
    ];

    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        if ($fieldName == 'language') {
            $newValues = Locale::where('id', $newValues)->first()?->name;
            $oldValues = Locale::where('id', $oldValues)->first()?->name;
        }

        return [
            $fieldName => [
                'name' => static::$historyFieldNames[$fieldName],
                'old'  => $oldValues ?: '',
                'new'  => $newValues ?: '',
            ],
        ];
    }
}
