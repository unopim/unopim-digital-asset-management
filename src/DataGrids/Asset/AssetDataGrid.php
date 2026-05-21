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
    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn = 'dam_assets.updated_at';

    protected $sortOrder = 'desc';

    protected $customFilterColumns = [];

    /**
     * Map of `property_filter_<md5>` column index → the property name it filters on.
     * Populated in prepareColumns() and consumed in processRequestedFilters().
     */
    protected array $propertyFilterMap = [];

    /**
     * Hard cap on the number of distinct "Add to Filter" property names exposed
     * as sidebar filters. Anything past this is dropped (logged once).
     */
    protected const PROPERTY_FILTER_LIMIT = 50;

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
        $prefix = DB::getTablePrefix();

        $queryBuilder = DB::table('dam_directories')
            ->join('dam_asset_directory', 'dam_directories.id', '=', 'dam_asset_directory.directory_id')
            ->join('dam_assets', 'dam_asset_directory.asset_id', '=', 'dam_assets.id')
            ->leftJoin('dam_asset_properties', 'dam_assets.id', '=', 'dam_asset_properties.dam_asset_id')
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
        $this->addFilter('tag', 'dam_tags.name');
        $this->addFilter('property_name', 'dam_asset_properties.name');
        $this->addFilter('property_value', 'dam_asset_properties.value');
        $this->addFilter('created_at', 'dam_assets.created_at');
        $this->addFilter('updated_at', 'dam_assets.updated_at');

        $this->customFilterColumns = [
            'directory_asset_id' => 'dam_asset_directory.asset_id',
            'directory_id'       => 'dam_directories.id',
        ];

        $service = app(DirectoryPermissionService::class);

        if (! $service->bypass()) {
            // Strict: only assets in directly-granted dirs. Ancestor dirs
            // visible in the tree (via canView expansion) must not leak their
            // assets here.
            $allowedIds = $service->directlyGrantedIds();

            if (empty($allowedIds)) {
                $queryBuilder->whereRaw('1 = 0');
            } else {
                $queryBuilder->whereIn('dam_directories.id', $allowedIds);
            }
        }

        return $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareColumns()
    {
        // Filterable properties (admin-flagged via "Add to Filter") render
        // BEFORE the static columns so they sit at the top of the sidebar in
        // sort_order. Ordering is governed by MIN(sort_order) ASC, then name.
        $this->registerFilterableProperties();

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
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'property_value',
            'label'      => trans('dam::app.admin.dam.index.datagrid.property-value'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
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
     * Read distinct property names flagged is_filterable=true and expose each as
     * a sidebar-only string filter on the assets grid.
     */
    protected function registerFilterableProperties(): void
    {
        // Order by the smallest sort_order seen for each property name so admins
        // control the sidebar order. Ties (or missing values, which default to 0)
        // fall back to alphabetical.
        $names = DB::table('dam_asset_properties')
            ->where('is_filterable', true)
            ->select('name', DB::raw('MIN(sort_order) as min_sort'))
            ->groupBy('name')
            ->orderBy('min_sort')
            ->orderBy('name')
            ->limit(self::PROPERTY_FILTER_LIMIT)
            ->pluck('name');

        foreach ($names as $name) {
            $index = 'property_filter_'.md5($name);
            $this->propertyFilterMap[$index] = $name;

            $this->addColumn([
                'index'      => $index,
                'label'      => $name,
                'type'       => 'string',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => false,
                'visible'    => false,
            ]);
        }
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

                if (isset($this->propertyFilterMap[$requestedColumn])) {
                    $propertyName = $this->propertyFilterMap[$requestedColumn];
                    $this->queryBuilder->where(function ($scope) use ($propertyName, $requestedValues) {
                        foreach ($requestedValues as $value) {
                            $scope->orWhereExists(function ($sub) use ($propertyName, $value) {
                                $sub->select(DB::raw(1))
                                    ->from('dam_asset_properties as pf')
                                    ->whereColumn('pf.dam_asset_id', 'dam_assets.id')
                                    ->where('pf.name', $propertyName)
                                    ->where('pf.value', 'LIKE', '%'.$value.'%');
                            });
                        }
                    });

                    continue;
                }

                if ($requestedColumn === 'directory_id') {
                    // Expand to descendants server-side; frontend sends only the selected id.
                    $expandedIds = collect();
                    foreach ($requestedValues as $rootId) {
                        $expandedIds->push((int) $rootId);
                        $expandedIds = $expandedIds->merge(
                            Directory::descendantsOf($rootId)->pluck('id')
                        );
                    }
                    $expandedIds = $expandedIds->unique()->values()->all();

                    if (empty($expandedIds)) {
                        $this->queryBuilder->whereRaw('1 = 0');
                    } else {
                        $this->queryBuilder->whereIn($this->customFilterColumns[$requestedColumn], $expandedIds);
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

                switch ($column->type) {
                    case ColumnTypeEnum::STRING->value:
                        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues) {
                            foreach ($requestedValues as $value) {
                                $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
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
