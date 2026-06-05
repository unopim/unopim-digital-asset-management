<?php

namespace Webkul\DAM\ApiDataSource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\DAM\Database\Eloquent\Builder;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

class AssetDataSource extends ApiDataSource
{
    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn = 'dam_assets.id';

    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(
        protected AssetRepository $assetRepository,
    ) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareApiQueryBuilder()
    {
        // Register the columns the REST list endpoint can filter on. Without
        // these, the base ApiDataSource rejects every filter with 422. Passing
        // the real table ('dam_assets') as filterTable also makes
        // operatorByFilter prefix columns correctly instead of a bogus
        // 'assets.' alias (which otherwise throws "unknown column" → 500).
        $this->addFilter('file_type', ['='], 'dam_assets');
        $this->addFilter('mime_type', ['=', 'LIKE'], 'dam_assets');
        $this->addFilter('extension', ['=', 'LIKE'], 'dam_assets');
        $this->addFilter('file_size', ['=', '<', '>', '<=', '>='], 'dam_assets');
        $this->addFilter('file_name', ['=', 'LIKE'], 'dam_assets');
        $this->addFilter('code', ['=', 'LIKE'], 'dam_assets');
        $this->addFilter('created_at', ['=', '>=', '<='], 'dam_assets');
        $this->addFilter('updated_at', ['=', '>=', '<='], 'dam_assets');

        return $this->assetRepository->queryBuilder();
    }

    /**
     * Inject directory permission scope into every query.
     * Called inside the scopeQuery closure in ApiDataSource::processRequestedFilters,
     * so $queryBuilder is the underlying Eloquent builder — mutations apply in place.
     */
    public function setDefaultFilters($queryBuilder)
    {
        $service = app(DirectoryPermissionService::class);

        if ($service->bypass()) {
            return $queryBuilder;
        }

        $allowedIds = $service->directlyGrantedIds();

        if (empty($allowedIds)) {
            $queryBuilder->whereRaw('1 = 0');

            return $queryBuilder;
        }

        $queryBuilder->whereIn('id', function ($sub) use ($allowedIds) {
            $sub->select('asset_id')
                ->from('dam_asset_directory')
                ->whereIn('directory_id', $allowedIds);
        });

        return $queryBuilder;
    }

    /**
     * Format data for API response.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map([$this, 'normalizeAsset'], $paginator['data'] ?? []);
    }

    /**
     * Get asset by its unique code (e.g. file name or identifier).
     *
     * @return array
     *
     * @throws ModelNotFoundException
     */
    public function getByCode(string $code)
    {
        $this->prepareForSingleData();

        $requestedFilters = [
            'code' => [
                [
                    'operator' => '=',
                    'value'    => $code,
                ],
            ],
        ];

        $this->queryBuilder = $this->processRequestedFilters($requestedFilters);

        $asset = $this->queryBuilder->first()?->toArray();

        if (! $asset) {
            throw new ModelNotFoundException(
                sprintf('Asset with code %s could not be found.', (string) $code)
            );
        }

        return $this->normalizeAsset($asset);
    }

    /**
     * Apply custom filters and operators.
     *
     * @param  Builder  $scopeQueryBuilder
     * @param  string  $requestedColumn
     * @param  array  $value
     * @return Builder
     */
    public function operatorByFilter($scopeQueryBuilder, $requestedColumn, $value)
    {
        $filterTable = isset($this->fieldFiltersAndOperators[$requestedColumn]['filterTable'])
            ? $this->fieldFiltersAndOperators[$requestedColumn]['filterTable'].'.'
            : 'assets.';

        switch ($requestedColumn) {
            case 'file_type':
                $scopeQueryBuilder->where($filterTable.'file_type', $value['value']);
                break;

            case 'mime_type':
                $scopeQueryBuilder->where($filterTable.'mime_type', 'LIKE', "%{$value['value']}%");
                break;

            case 'extension':
                $scopeQueryBuilder->where($filterTable.'extension', 'LIKE', "%{$value['value']}%");
                break;

            case 'code':
                // Free-text name search maps onto the file_name column.
                $scopeQueryBuilder->where($filterTable.'file_name', 'LIKE', "%{$value['value']}%");
                break;

            case 'file_size':
                $operator = $value['operator'] ?? '=';
                $scopeQueryBuilder->where($filterTable.'file_size', $operator, (int) $value['value']);
                break;

            case 'created_at':
            case 'updated_at':
                // Allow date range filtering if 'from' and 'to' keys exist
                if (isset($value['from']) && isset($value['to'])) {
                    $scopeQueryBuilder->whereBetween($filterTable.$requestedColumn, [$value['from'], $value['to']]);
                } else {
                    $scopeQueryBuilder->whereDate($filterTable.$requestedColumn, $value['value']);
                }
                break;

            default:
                $operator = $value['operator'] ?? '=';
                $scopeQueryBuilder->where($filterTable.$requestedColumn, $operator, $value['value']);
                break;
        }

        return $scopeQueryBuilder;
    }

    /**
     * Normalize asset data for API response.
     */
    protected function normalizeAsset(array $asset): array
    {
        $previewPath = isset($asset['path'])
            ? AssetHelper::getPreviewUrl(
                $asset['path'],
                isset($asset['file_size']) ? (int) $asset['file_size'] : null
            )
            : null;

        $responseData = [
            'id'           => $asset['id'],
            'file_name'    => $asset['file_name'],
            'file_type'    => $asset['file_type'] ?? null,
            'file_size'    => (int) ($asset['file_size'] ?? 0),
            'mime_type'    => $asset['mime_type'] ?? null,
            'extension'    => $asset['extension'] ?? null,
            'path'         => $asset['path'] ?? null,
            'preview_path' => $previewPath,
            'created_at'   => $asset['created_at'] ?? null,
            'updated_at'   => $asset['updated_at'] ?? null,
        ];

        return $responseData;
    }
}
