<?php

namespace Webkul\DAM\DataGrids\Asset;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Http\Controllers\FileController;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DataGrid\DataGrid;
use Webkul\DataGrid\Enums\ColumnTypeEnum;

class AssetDataGrid extends DataGrid
{
    protected $sortColumn = 'dam_assets.updated_at';

    protected $sortOrder = 'desc';

    protected $customFilterColumns = [];

    protected array $propNameMap = [];

    protected $itemsPerPage = 50;

    public function __construct(
        protected FileController $fileController
    ) {}

    public function prepareQueryBuilder()
    {
        $prefix = DB::getTablePrefix();

        $queryBuilder = DB::table('dam_directories')
            ->join('dam_asset_directory', 'dam_directories.id', '=', 'dam_asset_directory.directory_id')
            ->join('dam_assets', 'dam_asset_directory.asset_id', '=', 'dam_assets.id')
            ->leftJoin('dam_asset_tag', 'dam_assets.id', '=', 'dam_asset_tag.asset_id')
            ->leftJoin('dam_tags', 'dam_asset_tag.tag_id', '=', 'dam_tags.id')
            ->select(
                DB::raw('MIN('.$prefix.'dam_directories.id) as directory_id'),
                'dam_assets.id',
                'dam_assets.file_name',
                'dam_assets.file_type',
                'dam_assets.file_size',
                'dam_assets.mime_type',
                'dam_assets.extension',
                'dam_assets.path',
                'dam_assets.created_at',
                'dam_assets.updated_at',
                DB::raw('MIN('.$prefix.'dam_asset_directory.asset_id) as directory_asset_id'),
            )
            ->groupBy('dam_assets.id');

        $this->addFilter('id', 'dam_assets.id');
        $this->addFilter('file_name', 'dam_assets.file_name');
        $this->addFilter('extension', 'dam_assets.extension');
        $this->addFilter('path', 'dam_assets.path');
        $this->addFilter('tag', 'dam_tags.name');
        $this->addFilter('created_at', 'dam_assets.created_at');
        $this->addFilter('updated_at', 'dam_assets.updated_at');

        $this->customFilterColumns = [
            'directory_asset_id' => 'dam_asset_directory.asset_id',
            'directory_id'       => 'dam_directories.id',
        ];

        $service = app(DirectoryPermissionService::class);

        if (! $service->bypass()) {
            $allowedIds = $service->directlyGrantedIds();

            if (empty($allowedIds)) {
                $queryBuilder->whereRaw('1 = 0');
            } else {
                $queryBuilder->whereIn('dam_directories.id', $allowedIds);
            }
        }

        return $queryBuilder;
    }

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

                return $fileName ? AssetHelper::getDisplayFileName($fileName) : trans('dam::app.admin.dam.asset.datagrid.no-file-name');
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
                return isset($row->path) ? AssetHelper::getThumbnailUrl($row->path) : '';
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

        $this->addFilterablePropertyColumns();
    }

    protected function addFilterablePropertyColumns(): void
    {
        $props = DB::table('dam_asset_properties')
            ->select('name', DB::raw('MIN(sort_order) as sort_order'))
            ->where('is_filterable', true)
            ->groupBy('name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($props as $prop) {
            $normalizedName = preg_replace('/[^a-zA-Z0-9_]/', '_', $prop->name);
            $this->propNameMap[$normalizedName] = $prop->name;

            $this->addColumn([
                'index'      => 'prop_'.$normalizedName,
                'label'      => $prop->name,
                'type'       => 'string',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => false,
            ]);
        }
    }

    public function formatData(): array
    {
        $formattedData = parent::formatData();

        $formattedData['meta']['per_page_options'] = [50, 100, 150, 200, 250];

        return $formattedData;
    }

    public function processRequestedFilters(array $requestedFilters)
    {
        $prefix = DB::getTablePrefix();

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($scopeQueryBuilder) use ($prefix, $requestedValues) {
                    foreach ($requestedValues as $value) {
                        collect($this->columns)
                            ->filter(fn ($column) => $column->searchable
                                && $column->type !== ColumnTypeEnum::BOOLEAN->value
                                && $column->type !== ColumnTypeEnum::DATE_RANGE->value
                                && $column->type !== ColumnTypeEnum::DATE_TIME_RANGE->value)
                            ->each(fn ($column) => $scopeQueryBuilder->orWhereRaw(
                                'LOWER('.$prefix.$column->getDatabaseColumnName().') LIKE ?',
                                ['%'.strtolower($value).'%']
                            ));
                    }
                });
            } else {
                $column = collect($this->columns)->first(fn ($c) => $c->index === $requestedColumn);

                if ($requestedColumn === 'directory_id') {
                    $this->queryBuilder->where(function ($q) use ($requestedValues) {
                        foreach ($requestedValues as $rootId) {
                            $root = Directory::select('_lft', '_rgt')->find((int) $rootId);
                            if ($root) {
                                $q->orWhereBetween('dam_directories._lft', [$root->_lft, $root->_rgt]);
                            }
                        }
                    });

                    if (empty($requestedValues)) {
                        $this->queryBuilder->whereRaw('1 = 0');
                    }

                    continue;
                }

                if ($requestedColumn === 'directory_asset_id') {
                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedColumn, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($this->customFilterColumns[$requestedColumn], $value);
                        }
                    });

                    continue;
                }

                if (str_starts_with($requestedColumn, 'prop_')) {
                    $normalizedName = substr($requestedColumn, 5);
                    $propName = $this->propNameMap[$normalizedName] ?? $normalizedName;
                    $prefix = DB::getTablePrefix();

                    $this->queryBuilder->where(function ($scopeQueryBuilder) use ($prefix, $propName, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhereExists(function ($sub) use ($prefix, $propName, $value) {
                                $sub->select(DB::raw(1))
                                    ->from('dam_asset_properties')
                                    ->whereRaw("{$prefix}dam_asset_properties.dam_asset_id = {$prefix}dam_assets.id")
                                    ->where('name', $propName)
                                    ->whereRaw('LOWER(value) LIKE ?', ['%'.strtolower($value).'%']);
                            });
                        }
                    });

                    continue;
                }

                switch ($column->type) {
                    case ColumnTypeEnum::STRING->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($prefix, $column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhereRaw(
                                    'LOWER('.$prefix.$column->getDatabaseColumnName().') LIKE ?',
                                    ['%'.strtolower($value).'%']
                                );
                            }
                        });

                        break;

                    case ColumnTypeEnum::INTEGER->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                            }
                        });

                        break;

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
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($prefix, $column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhereRaw(
                                    'LOWER('.$prefix.$column->getDatabaseColumnName().') LIKE ?',
                                    ['%'.strtolower($value).'%']
                                );
                            }
                        });

                        break;
                }
            }
        }

        return $this->queryBuilder;
    }

    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('dam.asset.update')) {
            $this->addMassAction([
                'title'   => trans('dam::app.admin.dam.tag.mass-action.assign-tags'),
                'url'     => route('admin.dam.assets.mass_assign_tags'),
                'method'  => 'POST',
                'options' => ['actionType' => 'assign-tags'],
            ]);
        }

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
