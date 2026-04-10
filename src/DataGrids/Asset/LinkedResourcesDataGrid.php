<?php

namespace Webkul\DAM\DataGrids\Asset;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class LinkedResourcesDataGrid extends DataGrid
{
    protected $sortOrder = 'desc';

    /**
     * {@inheritDoc}
     */
    protected $itemsPerPage = 10;

    /**
     * {@inheritDoc}
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('dam_asset_resource_mappings')
            ->leftJoin('products', 'dam_asset_resource_mappings.product_id', '=', 'products.id')
            ->leftJoin('categories', 'dam_asset_resource_mappings.category_id', '=', 'categories.id')
            ->select(
                'dam_asset_resource_mappings.id',
                'dam_asset_resource_mappings.type',
                'products.sku as product_sku',
                'categories.code as category_code',
                'dam_asset_resource_mappings.product_id',
                'dam_asset_resource_mappings.category_id',
                'dam_asset_resource_mappings.dam_asset_id',
                'dam_asset_resource_mappings.created_at',
                'dam_asset_resource_mappings.updated_at'
            );

        $assetId = request()->get('dam_asset_id') ?? null;

        if ($assetId) {
            $queryBuilder = $queryBuilder->where('dam_asset_resource_mappings.dam_asset_id', $assetId);
        }

        $this->addFilter('type', 'dam_asset_resource_mappings.type');

        return $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.resource-type'),
            'type'       => 'dropdown',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => ucfirst($row->type),
            'options'    => [
                'type' => 'basic',

                'params' => [
                    'options' => [
                        [
                            'label' => trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.product'),
                            'value' => 'product',
                        ], [
                            'label' => trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.category'),
                            'value' => 'category',
                        ],
                    ],
                ],
            ],
        ]);

        $this->addColumn([
            'index'      => 'resource',
            'label'      => trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.resource'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return strtolower($row->type) === 'product'
                    ? trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.product-sku').$row->product_sku
                    : trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.category code').$row->category_code;
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-view',
            'title'  => trans('dam::app.admin.dam.asset.linked-resources.index.datagrid.resource-view'),
            'method' => 'GET',
            'url'    => function ($row) {
                return strtolower($row->type) === 'product' ? route('admin.catalog.products.edit', $row->product_id) : route('admin.catalog.categories.edit', $row->category_id);
            },
        ]);
    }
}
