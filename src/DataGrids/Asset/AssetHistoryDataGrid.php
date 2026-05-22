<?php

namespace Webkul\DAM\DataGrids\Asset;

use Webkul\HistoryControl\DataGrids\HistoryDataGrid;

class AssetHistoryDataGrid extends HistoryDataGrid
{
    public function prepareActions()
    {
        parent::prepareActions();

        if (bouncer()->hasPermission('dam.asset.update')) {
            $this->addAction([
                'icon'   => 'icon-dam-restore',
                'title'  => trans('dam::app.history.restore'),
                'method' => 'POST',
                'url'    => function ($row) {
                    return route('admin.dam.history.restore', ['asset' => $this->getEntityId()])
                        .'?version_id='.$row->version_id;
                },
            ]);
        }
    }
}
