<?php

namespace Webkul\DAM\DataGrids\Asset;

class PickerDataGrid extends AssetDataGrid
{
    protected $itemsPerPage = 50;

    public function prepareQueryBuilder()
    {
        $queryBuilder = parent::prepareQueryBuilder();

        $queryBuilder->addSelect('dam_assets.path as storage_file_path');

        return $queryBuilder;
    }

    public function formatData(): array
    {
        $formattedData = parent::formatData();

        $formattedData['meta']['per_page_options'] = [50, 100, 150, 200, 250];

        return $formattedData;
    }
}
