<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Explorer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;

class ExplorerDataController extends Controller
{
    public function __construct(
        protected DirectoryPermissionService $permissionService
    ) {}

    public function filterOptions(): JsonResponse
    {
        $properties = DB::table('dam_asset_properties')
            ->where('is_filterable', true)
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        return response()->json([
            'file_types' => ['image', 'video', 'audio', 'document', 'spreadsheet', 'csv'],
            'properties' => $properties,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'directory_id'        => 'required|integer|min:1|exists:dam_directories,id',
            'search'              => 'nullable|string|max:255',
            'page'                => 'nullable|integer|min:1',
            'per_page'            => 'nullable|integer|min:1|max:250',
            'sort_by'             => 'nullable|in:name,size,updated_at',
            'sort_order'          => 'nullable|in:asc,desc',
            'filter_file_name'    => 'nullable|string|max:255',
            'filter_extension'    => 'nullable|string|max:50',
            'filter_file_type'    => 'nullable|string|in:image,video,audio,document,spreadsheet,csv',
            'filter_tag'          => 'nullable|string|max:255',
            'filter_created_from' => 'nullable|date',
            'filter_created_to'   => 'nullable|date',
            'filter_updated_from' => 'nullable|date',
            'filter_updated_to'   => 'nullable|date',
        ]);

        $dir = Directory::find($request->integer('directory_id'));

        if (! $dir) {
            return response()->json(['message' => trans('dam::app.admin.explorer.not-found')], 404);
        }

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($dir->id)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        // Collect filter params
        $filterFileName = $request->input('filter_file_name');
        $filterExtension = $request->input('filter_extension');
        $filterFileType = $request->input('filter_file_type');
        $filterTag = $request->input('filter_tag');
        $filterCreatedFrom = $request->input('filter_created_from');
        $filterCreatedTo = $request->input('filter_created_to');
        $filterUpdatedFrom = $request->input('filter_updated_from');
        $filterUpdatedTo = $request->input('filter_updated_to');

        // Dynamic property filters: filter_prop_{name}
        $filterProps = collect($request->all())
            ->filter(fn ($v, $k) => str_starts_with($k, 'filter_prop_') && is_string($v) && $v !== '')
            ->mapWithKeys(fn ($v, $k) => [substr($k, strlen('filter_prop_')) => $v]);

        // --- Directories ---
        $dirQuery = Directory::query();

        if ($search) {
            // whereDescendantOf uses the nested-set _lft/_rgt range — one efficient index scan
            $dirQuery->whereDescendantOf($dir)
                ->where(DB::raw('LOWER(name)'), 'like', '%'.strtolower($search).'%')
                ->limit(200); // cap search results — full subtree scan on large trees otherwise unbounded
        } else {
            $dirQuery->where('parent_id', $dir->id);
        }

        // viewableIds() includes ancestors of granted dirs so that transit
        // directories (e.g. "webkul" when the user only has "webkul/akeneo")
        // appear in the listing and allow the user to navigate down.
        if (! $this->permissionService->bypass()) {
            $dirQuery->whereIn('id', $this->permissionService->viewableIds());
        }

        $dirSortColumn = match ($sortBy) {
            'updated_at' => 'updated_at',
            default      => 'name',
        };

        // Memoised per request — safe to call here without re-querying.
        $directlyGrantedIds = $this->permissionService->bypass()
            ? null
            : $this->permissionService->directlyGrantedIds();

        $directories = $dirQuery->withCount(['assets', 'children'])
            ->orderBy($dirSortColumn, $sortOrder)
            ->get(['id', 'name', 'parent_id', 'updated_at'])
            ->map(fn (Directory $d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'parent_id'      => $d->parent_id,
                'assets_count'   => $d->assets_count ?? 0,
                'children_count' => $d->children_count ?? 0,
                'updated_at'     => $d->updated_at,
                // true = user may upload/rename/delete inside this dir;
                // false = transit ancestor, visible for navigation only.
                'can_access'     => $directlyGrantedIds === null || in_array($d->id, $directlyGrantedIds),
            ]);

        // --- Assets ---
        $buildAssetQuery = function () use (
            $dir, $search,
            $filterFileName, $filterExtension, $filterFileType, $filterTag,
            $filterCreatedFrom, $filterCreatedTo,
            $filterUpdatedFrom, $filterUpdatedTo,
            $filterProps
        ) {
            $prefix = DB::getTablePrefix();

            $q = Asset::query()
                ->join(
                    'dam_asset_directory',
                    'dam_asset_directory.asset_id',
                    '=',
                    'dam_assets.id'
                );

            if ($search) {
                // Subquery keeps all IDs in the DB — never loads 10k dir IDs into PHP
                $dirTable = (new Directory)->getTable();
                $subtreeSubquery = DB::table($dirTable)
                    ->select('id')
                    ->where('_lft', '>=', $dir->_lft)
                    ->where('_rgt', '<=', $dir->_rgt);

                $lowerSearch = '%'.strtolower($search).'%';
                $q->whereIn('dam_asset_directory.directory_id', $subtreeSubquery)
                    ->whereRaw('LOWER('.$prefix.'dam_assets.file_name) LIKE ?', [$lowerSearch]);
            } else {
                $q->where('dam_asset_directory.directory_id', $dir->id);
            }

            if (! $this->permissionService->bypass()) {
                $q->whereIn(
                    'dam_asset_directory.directory_id',
                    $this->permissionService->directlyGrantedIds()
                );
            }

            // Apply filters
            if ($filterFileName) {
                $q->whereRaw('LOWER('.$prefix.'dam_assets.file_name) LIKE ?', ['%'.strtolower($filterFileName).'%']);
            }

            if ($filterExtension) {
                $q->whereRaw('LOWER('.$prefix.'dam_assets.extension) LIKE ?', ['%'.strtolower($filterExtension).'%']);
            }

            if ($filterFileType) {
                $q->where('dam_assets.file_type', $filterFileType);
            }

            if ($filterTag) {
                $lowerTag = '%'.strtolower($filterTag).'%';
                $q->whereExists(
                    DB::table('dam_tags')
                        ->join('dam_asset_tag', 'dam_tags.id', '=', 'dam_asset_tag.tag_id')
                        ->whereColumn('dam_asset_tag.asset_id', 'dam_assets.id')
                        ->whereRaw('LOWER('.$prefix.'dam_tags.name) LIKE ?', [$lowerTag])
                        ->select(DB::raw(1))
                );
            }

            if ($filterCreatedFrom) {
                $q->where('dam_assets.created_at', '>=', $filterCreatedFrom.' 00:00:00');
            }

            if ($filterCreatedTo) {
                $q->where('dam_assets.created_at', '<=', $filterCreatedTo.' 23:59:59');
            }

            if ($filterUpdatedFrom) {
                $q->where('dam_assets.updated_at', '>=', $filterUpdatedFrom.' 00:00:00');
            }

            if ($filterUpdatedTo) {
                $q->where('dam_assets.updated_at', '<=', $filterUpdatedTo.' 23:59:59');
            }

            foreach ($filterProps as $propName => $propValue) {
                $q->whereExists(function ($sub) use ($prefix, $propName, $propValue) {
                    $sub->select(DB::raw(1))
                        ->from('dam_asset_properties')
                        ->whereRaw("{$prefix}dam_asset_properties.dam_asset_id = {$prefix}dam_assets.id")
                        ->where('name', $propName)
                        ->whereRaw('LOWER(value) LIKE ?', ['%'.strtolower($propValue).'%']);
                });
            }

            return $q;
        };

        $sortColumn = match ($sortBy) {
            'size'       => 'dam_assets.file_size',
            'updated_at' => 'dam_assets.updated_at',
            default      => 'dam_assets.file_name',
        };

        // Build once — clone for count to avoid re-running the subtree subquery
        $assetQuery = $buildAssetQuery();
        $totalAssets = (clone $assetQuery)->distinct()->count('dam_assets.id');

        $assets = $assetQuery
            ->select([
                'dam_assets.id', 'dam_assets.file_name', 'dam_assets.file_type',
                'dam_assets.extension', 'dam_assets.file_size', 'dam_assets.path',
                'dam_assets.mime_type', 'dam_assets.updated_at',
            ])
            ->groupBy(
                'dam_assets.id', 'dam_assets.file_name', 'dam_assets.file_type',
                'dam_assets.extension', 'dam_assets.file_size', 'dam_assets.path',
                'dam_assets.mime_type', 'dam_assets.updated_at'
            )
            ->orderBy($sortColumn, $sortOrder)
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($asset) {
                $asset->path = $asset->path
                    ? route('admin.dam.file.thumbnail', ['path' => urlencode($asset->path)])
                    : '';

                return $asset;
            });

        $breadcrumb = $dir->ancestors()
            ->defaultOrder()
            ->get(['id', 'name'])
            ->map(fn (Directory $a) => ['id' => $a->id, 'name' => $a->name])
            ->push(['id' => $dir->id, 'name' => $dir->name])
            ->values()
            ->toArray();

        return response()->json([
            'directories' => $directories,
            'assets'      => $assets,
            'breadcrumb'  => $breadcrumb,
            'meta'        => [
                'directory_id'      => $dir->id,
                'total_assets'      => $totalAssets,
                'total_directories' => $directories->count(),
                'current_page'      => $page,
                'last_page'         => max(1, (int) ceil($totalAssets / max($perPage, 1))),
                'per_page'          => $perPage,
                // Whether the current user may perform write operations in this dir.
                // false when the dir is only visible as a transit ancestor.
                'can_access_current' => $this->permissionService->bypass() || $this->permissionService->canAccess($dir->id),
            ],
        ]);
    }

    public function countItems(Request $request): JsonResponse
    {
        $request->validate([
            'asset_ids'       => 'nullable|array',
            'asset_ids.*'     => 'integer|min:1',
            'directory_ids'   => 'nullable|array',
            'directory_ids.*' => 'integer|min:1',
        ]);

        $assetIds = array_unique(array_map('intval', $request->input('asset_ids', [])));
        $dirIds = array_unique(array_map('intval', $request->input('directory_ids', [])));

        $fileCount = count($assetIds);

        if (! empty($dirIds)) {
            // Load roots once, then get all subtree IDs in ONE range-union query
            $roots = Directory::whereIn('id', $dirIds)->get(['id', '_lft', '_rgt']);

            $subtreeQuery = Directory::query();
            foreach ($roots as $i => $root) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $subtreeQuery->{$method}(function ($q) use ($root) {
                    $q->where('_lft', '>=', $root->_lft)
                        ->where('_rgt', '<=', $root->_rgt);
                });
            }
            $allDirIds = $subtreeQuery->pluck('id');

            $fileCount += DB::table('dam_asset_directory')
                ->whereIn('directory_id', $allDirIds)
                ->distinct()
                ->count('asset_id');
        }

        return response()->json(['file_count' => $fileCount]);
    }
}
