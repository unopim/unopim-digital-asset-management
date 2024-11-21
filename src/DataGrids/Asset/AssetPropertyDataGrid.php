<?php

namespace Webkul\DAM\DataGrids\Asset;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataGrid\DataGrid;

class AssetPropertyDataGrid extends DataGrid
{
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * {@inheritDoc}
     */
    public function prepareQueryBuilder()
    {
        $assetId = request()->id;

        $queryBuilder = DB::table('dam_asset_properties')
            ->select(
                'dam_asset_properties.id',
                'dam_asset_properties.name',
                'dam_asset_properties.type',
                'dam_asset_properties.language',
                'dam_asset_properties.value',
                'dam_asset_properties.created_at',
                'dam_asset_properties.updated_at',
                'dam_asset_properties.dam_asset_id',
            )
            ->orderBy('dam_asset_properties.updated_at', 'desc')
            ->where('dam_asset_properties.dam_asset_id', $assetId);

        return $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('dam::app.admin.dam.asset.properties.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('dam::app.admin.dam.asset.properties.index.datagrid.type'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'language',
            'label'      => trans('dam::app.admin.dam.asset.properties.index.datagrid.language'),
            'type'       => 'dropdown',
            'options'    => [
                'type' => 'basic',

                'params' => [
                    'options' => $this->getActiveLocales(),
                ],
            ],
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $local = $this->localeRepository->findOneByField('id', $row->language);

                return \Locale::getDisplayName($local?->code, core()->getCurrentLocale()?->code);
            },
        ]);

        $this->addColumn([
            'index'      => 'value',
            'label'      => trans('dam::app.admin.dam.asset.properties.index.datagrid.value'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
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
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('dam::app.admin.dam.asset.properties.index.datagrid.edit'),
            'method' => 'GET',
            'url'    => function ($row) {

                return route('admin.dam.asset.property.edit', $row->id);
            },
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('dam::app.admin.dam.asset.properties.index.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('admin.dam.asset.properties.delete', ['asset_id' => $row->dam_asset_id, 'id' => $row->id]);
            },
        ]);
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('dam.asset.property.delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.products.index.datagrid.delete'),
                'url'     => route('admin.dam.asset.properties.mass_delete', ['asset_id' => request()->id]),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }

    public function getActiveLocales()
    {
        return array_map(
            function ($data) {
                $data['value'] = $data['id'];
                $data['label'] = $data['name'];

                return $data;
            },
            $this->localeRepository->getActiveLocales()->toArray()
        );
    }
}
