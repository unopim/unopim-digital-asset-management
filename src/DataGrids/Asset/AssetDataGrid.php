<?php

namespace Webkul\DAM\DataGrids\Asset;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Http\Controllers\FileController;
use Webkul\DataGrid\DataGrid;
use Webkul\DataGrid\Enums\ColumnTypeEnum;

class AssetDataGrid extends DataGrid
{
    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn = 'dam_assets.updated_at';

    protected $sortOrder = 'desc';

    protected $customFilterColumns = [];

    /**
     * {@inheritDoc}
     */
    protected $itemsPerPage = 50;

    public function __construct(
        protected FileController $fileController
    ) {}

    /**
     * {@inheritDoc}
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('dam_directories')
            ->distinct()
            ->join('dam_asset_directory', 'dam_directories.id', '=', 'dam_asset_directory.directory_id')
            ->join('dam_assets', 'dam_asset_directory.asset_id', '=', 'dam_assets.id')
            ->leftJoin('dam_asset_properties', 'dam_assets.id', '=', 'dam_asset_properties.dam_asset_id')
            ->leftJoin('dam_asset_tag', 'dam_assets.id', '=', 'dam_asset_tag.asset_id')
            ->leftJoin('dam_tags', 'dam_asset_tag.tag_id', '=', 'dam_tags.id')
            ->select(
                'dam_directories.id as directory_id',
                'dam_assets.id',
                'dam_assets.file_name',
                'dam_assets.file_type',
                'dam_assets.file_size',
                'dam_assets.mime_type',
                'dam_assets.extension',
                'dam_assets.path',
                'dam_assets.created_at',
                'dam_assets.updated_at',
                'dam_asset_directory.asset_id as directory_asset_id',
            )
            ->groupBy('dam_assets.id');

        $this->addFilter('id', 'dam_assets.id');
        $this->addFilter('tag', 'dam_tags.name');
        $this->addFilter('property_name', 'dam_asset_properties.name');
        $this->addFilter('property_value', 'dam_asset_properties.value');
        $this->addFilter('created_at', 'dam_assets.created_at');
        $this->addFilter('updated_at', 'dam_assets.updated_at');

        $this->customFilterColumns = [
            'directory_asset_id' => 'dam_asset_directory.asset_id',
            'directory_id'       => 'dam_directories.id',
        ];

        return $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'file_name',
            'label'      => trans('dam::app.admin.dam.index.datagrid.file-name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $fileName = $row->file_name;

                return $fileName ? AssetHelper::getDisplayFileName($fileName) : trans('no file name');
            },
        ]);

        $this->addColumn([
            'index'      => 'tag',
            'label'      => trans('dam::app.admin.dam.index.datagrid.tags'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'property_name',
            'label'      => trans('dam::app.admin.dam.index.datagrid.property-name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'property_value',
            'label'      => trans('dam::app.admin.dam.index.datagrid.property-value'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'extension',
            'label'      => trans('dam::app.admin.dam.index.datagrid.extension'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'path',
            'label'      => trans('dam::app.admin.dam.index.datagrid.path'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => function ($row) {
                return isset($row->path) ? route('admin.dam.file.thumbnail', ['path' => urlencode($row->path)]) : '';
            },
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('dam::app.admin.dam.index.datagrid.created-at'),
            'type'       => 'date_range',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('dam::app.admin.dam.index.datagrid.updated-at'),
            'type'       => 'date_range',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function formatData(): array
    {
        $formattedData = parent::formatData();

        $formattedData['meta']['per_page_options'] = [50, 100, 150, 200, 250];

        return $formattedData;
    }

    /**
     * {@inheritDoc}
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
                    foreach ($requestedValues as $value) {
                        collect($this->columns)
                            ->filter(fn ($column) => $column->searchable && $column->type !== ColumnTypeEnum::BOOLEAN->value)
                            ->each(fn ($column) => $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%'));
                    }
                });
            } else {
                $column = collect($this->columns)->first(fn ($c) => $c->index === $requestedColumn);

                if (in_array($requestedColumn, ['directory_id', 'directory_asset_id'])) {
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedColumn, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($this->customFilterColumns[$requestedColumn], $value);
                        }
                    });

                    continue;
                }

                switch ($column->type) {
                    case ColumnTypeEnum::STRING->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
                            }
                        });

                    case ColumnTypeEnum::INTEGER->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                            }
                        });

                    case ColumnTypeEnum::DROPDOWN->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                            }
                        });

                        break;

                    case ColumnTypeEnum::DATE_RANGE->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [
                                    ($value[0] ?? '').' 00:00:01',
                                    ($value[1] ?? '').' 23:59:59',
                                ]);
                            }
                        });

                        break;
                    case ColumnTypeEnum::DATE_TIME_RANGE->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [$value[0] ?? '', $value[1] ?? '']);
                            }
                        });

                        break;

                    default:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
                            }
                        });

                        break;
                }
            }
        }

        return $this->queryBuilder;
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('dam.asset.mass_delete')) {
            $this->addMassAction([
                'title'   => trans('admin::app.catalog.products.index.datagrid.delete'),
                'url'     => route('admin.dam.assets.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
