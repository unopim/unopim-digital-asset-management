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
            'per_page'            => 'nullable|integer|min:1|max:100',
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
            $dirQuery->whereDescendantOf($dir)
                ->where(DB::raw('LOWER(name)'), 'like', '%'.strtolower($search).'%');
        } else {
            $dirQuery->where('parent_id', $dir->id);
        }

        if (! $this->permissionService->bypass()) {
            $dirQuery->whereIn('id', $this->permissionService->directlyGrantedIds());
        }

        $directories = $dirQuery->withCount(['assets', 'children'])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (Directory $d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'parent_id'      => $d->parent_id,
                'assets_count'   => $d->assets_count ?? 0,
                'children_count' => $d->children_count ?? 0,
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
                $subtreeIds = Directory::whereDescendantOrSelf($dir)->pluck('id');
                $lowerSearch = '%'.strtolower($search).'%';
                $q->whereIn('dam_asset_directory.directory_id', $subtreeIds)
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

        $totalAssets = $buildAssetQuery()->distinct()->count('dam_assets.id');

        $assets = $buildAssetQuery()
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
                'last_page'         => (int) ceil($totalAssets / max($perPage, 1)),
                'per_page'          => $perPage,
            ],
        ]);
    }
}
