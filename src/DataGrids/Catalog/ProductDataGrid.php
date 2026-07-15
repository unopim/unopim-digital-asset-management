<?php

namespace Webkul\DAM\DataGrids\Catalog;

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid as BaseProductDataGrid;
use Webkul\DAM\Repositories\AssetRepository;

class ProductDataGrid extends BaseProductDataGrid
{
    protected function applyFilterTypeOptions(array $column, $attribute)
    {
        if ($attribute->type === 'asset') {
            $column['closure'] = $this->getAssetClosure();

            return $column;
        }

        return parent::applyFilterTypeOptions($column, $attribute);
    }

    protected function getAssetClosure()
    {
        return function ($value) {
            if (empty($value)) {
                return '';
            }

            $ids = is_array($value) ? $value : explode(',', (string) $value);

            $firstId = trim((string) ($ids[0] ?? ''));

            if ($firstId === '') {
                return '';
            }

            $asset = app(AssetRepository::class)->find($firstId);

            if (! $asset) {
                return '';
            }

            $url = $asset->file_type === 'image'
                ? route('admin.dam.file.thumbnail', ['path' => urlencode($asset->path)])
                : $this->getAssetPlaceholder($asset->file_type);

            return '<img src="'.$url.'" alt="'.e($asset->file_name).'" class="h-[60px] w-[60px] min-w-[60px] max-w-[60px] rounded-lg border border-gray-300 shadow-sm object-cover" />';
        };
    }

    protected function getAssetPlaceholder(?string $fileType): string
    {
        $placeholders = [
            'video'    => 'storage/dam/grid/video.svg',
            'audio'    => 'storage/dam/grid/audio.svg',
            'document' => 'storage/dam/grid/file.svg',
        ];

        return asset($placeholders[$fileType] ?? 'storage/dam/grid/unspecified.svg');
    }
}
