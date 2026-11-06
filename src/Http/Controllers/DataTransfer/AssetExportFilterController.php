<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\DataTransfer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\DAM\Http\Requests\DataTransfer\ExportFilterOptionsRequest;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Services\DirectoryPermissionService;

class AssetExportFilterController extends Controller
{
    protected const PER_PAGE = 20;

    protected const DEFAULT_PAGE = 1;

    public function __construct(
        protected DirectoryPermissionService $directoryPermissionService,
    ) {}

    public function directories(ExportFilterOptionsRequest $request): JsonResponse
    {
        $this->ensureExportPermission();

        $query = Directory::query()->whereNotNull('parent_id');

        if (! $this->directoryPermissionService->bypass()) {
            $query->whereIn('id', $this->directoryPermissionService->viewableIds());
        }

        return $this->paginateOptions(
            $request,
            $query,
            searchColumn: 'name',
            identifierColumn: 'id',
            columns: ['id', 'name'],
            mapper: fn (Directory $directory): array => [
                'id'    => (string) $directory->id,
                'label' => $directory->name,
            ],
        );
    }

    public function tags(ExportFilterOptionsRequest $request): JsonResponse
    {
        $this->ensureExportPermission();

        return $this->paginateOptions(
            $request,
            Tag::query(),
            searchColumn: 'name',
            identifierColumn: 'name',
            columns: ['name'],
            mapper: fn (Tag $tag): array => [
                'name'  => $tag->name,
                'label' => $tag->name,
            ],
        );
    }

    public function fileTypes(ExportFilterOptionsRequest $request): JsonResponse
    {
        $this->ensureExportPermission();

        $options = collect(['image', 'video', 'audio', 'document'])
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => trans('dam::app.data-transfer.exporters.assets.filters.file-types.'.$type),
            ]);

        $identifiers = $request->identifierValues();

        if ($identifiers !== null) {
            $options = $options->whereIn('value', $identifiers)->values();
        } elseif (($search = $request->searchTerm()) !== '') {
            $options = $options->filter(
                fn (array $option): bool => stripos((string) $option['label'], $search) !== false
            )->values();
        }

        return new JsonResponse([
            'options'  => $options->values(),
            'page'     => self::DEFAULT_PAGE,
            'lastPage' => self::DEFAULT_PAGE,
        ]);
    }

    protected function ensureExportPermission(): void
    {
        if (! bouncer()->hasPermission('data_transfer.export')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }
    }

    /**
     * Search, scope and paginate an option source entirely in SQL.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, string>  $columns
     * @param  callable(Model): array<string, string>  $mapper
     */
    protected function paginateOptions(
        ExportFilterOptionsRequest $request,
        Builder $query,
        string $searchColumn,
        string $identifierColumn,
        array $columns,
        callable $mapper,
    ): JsonResponse {
        $identifiers = $request->identifierValues();

        if ($identifiers !== null) {
            $options = $query->whereIn($identifierColumn, $identifiers)
                ->orderBy($searchColumn)
                ->get($columns)
                ->map($mapper)
                ->values();

            return new JsonResponse([
                'options'  => $options,
                'page'     => self::DEFAULT_PAGE,
                'lastPage' => self::DEFAULT_PAGE,
            ]);
        }

        $search = $request->searchTerm();

        if ($search !== '') {
            $query->where($searchColumn, 'like', '%'.$search.'%');
        }

        $paginator = $query->orderBy($searchColumn)->paginate(
            self::PER_PAGE,
            $columns,
            'page',
            $request->pageNumber(),
        );

        return new JsonResponse([
            'options'  => $paginator->getCollection()->map($mapper)->values(),
            'page'     => $paginator->currentPage(),
            'lastPage' => max(self::DEFAULT_PAGE, $paginator->lastPage()),
        ]);
    }
}
