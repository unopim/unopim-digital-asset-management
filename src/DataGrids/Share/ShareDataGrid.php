<?php

namespace Webkul\DAM\DataGrids\Share;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Share;
use Webkul\DataGrid\DataGrid;

class ShareDataGrid extends DataGrid
{
    protected $sortColumn = 'dam_shares.created_at';

    protected $sortOrder = 'desc';

    protected $itemsPerPage = 25;

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('dam_shares')
            ->leftJoin('admins', 'dam_shares.created_by', '=', 'admins.id')
            ->leftJoin('dam_assets', function ($join) {
                $join->on('dam_assets.id', '=', 'dam_shares.target_id')
                    ->where('dam_shares.share_type', '=', Share::TYPE_ASSET);
            })
            ->leftJoin('dam_directories', function ($join) {
                $join->on('dam_directories.id', '=', 'dam_shares.target_id')
                    ->where('dam_shares.share_type', '=', Share::TYPE_DIRECTORY);
            })
            ->select(
                'dam_shares.id',
                'dam_shares.token',
                'dam_shares.share_type',
                'dam_shares.target_id',
                'dam_shares.expires_at',
                'dam_shares.revoked_at',
                'dam_shares.view_count',
                'dam_shares.download_count',
                'dam_shares.last_accessed_at',
                'dam_shares.created_at',
                DB::raw('COALESCE('.DB::getTablePrefix().'admins.name, NULL) as created_by_name'),
                DB::raw('COALESCE('.DB::getTablePrefix().'dam_assets.file_name, '.DB::getTablePrefix().'dam_directories.name) as target_name'),
            );

        $this->addFilter('share_type', 'dam_shares.share_type');
        $this->addFilter('created_at', 'dam_shares.created_at');
        $this->addFilter('expires_at', 'dam_shares.expires_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'share_type',
            'label'      => trans('dam::app.admin.dam.share.datagrid.type'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => ucfirst((string) $row->share_type),
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => [
                        ['label' => trans('dam::app.admin.dam.share.datagrid.asset'), 'value' => Share::TYPE_ASSET],
                        ['label' => trans('dam::app.admin.dam.share.datagrid.directory'), 'value' => Share::TYPE_DIRECTORY],
                    ],
                ],
            ],
        ]);

        $this->addColumn([
            'index'      => 'target_name',
            'label'      => trans('dam::app.admin.dam.share.datagrid.target'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'created_by_name',
            'label'      => trans('dam::app.admin.dam.share.datagrid.created-by'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'expires_at',
            'label'      => trans('dam::app.admin.dam.share.datagrid.expires-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->expires_at
                ? Carbon::parse($row->expires_at)->toDateTimeString()
                : trans('dam::app.admin.dam.share.datagrid.never'),
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('dam::app.admin.dam.share.datagrid.status'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                if ($row->revoked_at) {
                    return trans('dam::app.admin.dam.share.datagrid.status-revoked');
                }
                if ($row->expires_at && Carbon::parse($row->expires_at)->isPast()) {
                    return trans('dam::app.admin.dam.share.datagrid.status-expired');
                }

                return trans('dam::app.admin.dam.share.datagrid.status-active');
            },
        ]);

        $this->addColumn([
            'index'      => 'view_count',
            'label'      => trans('dam::app.admin.dam.share.datagrid.views'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'download_count',
            'label'      => trans('dam::app.admin.dam.share.datagrid.downloads'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('dam::app.admin.dam.share.datagrid.created-at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('dam.shares.revoke')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('dam::app.admin.dam.share.datagrid.revoke'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.dam.shares.destroy', $row->id),
            ]);
        }
    }
}
