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
        abort_unless(
            bouncer()->hasPermission('dam.asset.view'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

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
        abort_unless(
            bouncer()->hasPermission('dam.asset.view'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

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

        $subtreeDirIds = Directory::query()
            ->whereBetween('_lft', [$dir->_lft, $dir->_rgt])
            ->pluck('id')
            ->all();

        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $filterFileName = $request->input('filter_file_name');
        $filterExtension = $request->input('filter_extension');
        $filterFileType = $request->input('filter_file_type');
        $filterTag = $request->input('filter_tag');
        $filterCreatedFrom = $request->input('filter_created_from');
        $filterCreatedTo = $request->input('filter_created_to');
        $filterUpdatedFrom = $request->input('filter_updated_from');
        $filterUpdatedTo = $request->input('filter_updated_to');

        $filterProps = collect($request->all())
            ->filter(fn ($v, $k) => str_starts_with($k, 'filter_prop_') && is_string($v) && $v !== '')
            ->mapWithKeys(fn ($v, $k) => [substr($k, strlen('filter_prop_')) => $v]);

        $dirQuery = Directory::query();

        if ($search) {

            $dirQuery->where('name', $likeOperator, '%'.strtolower($search).'%')
                ->whereIn('id', $subtreeDirIds)
                ->where('id', '!=', $dir->id)
                ->limit(200);
        } else {
            $dirQuery->where('parent_id', $dir->id);
        }

        if (! $this->permissionService->bypass()) {
            $dirQuery->whereIn('id', $this->permissionService->viewableIds());
        }

        $dirSortColumn = match ($sortBy) {
            'updated_at' => 'updated_at',
            default      => 'name',
        };

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
                'can_access'     => $directlyGrantedIds === null || in_array($d->id, $directlyGrantedIds),
            ]);

        $globalAssetScope = (bool) (
            $search || $filterFileName || $filterExtension || $filterFileType || $filterTag
            || $filterCreatedFrom || $filterCreatedTo || $filterUpdatedFrom || $filterUpdatedTo
            || $filterProps->isNotEmpty()
        );

        $buildAssetQuery = function () use (
            $dir, $search, $globalAssetScope, $directlyGrantedIds, $likeOperator, $subtreeDirIds,
            $filterFileName, $filterExtension, $filterFileType, $filterTag,
            $filterCreatedFrom, $filterCreatedTo,
            $filterUpdatedFrom, $filterUpdatedTo,
            $filterProps
        ) {
            $prefix = DB::getTablePrefix();

            $q = Asset::query();

            $q->whereExists(function ($sub) use ($dir, $globalAssetScope, $directlyGrantedIds, $subtreeDirIds) {
                $sub->select(DB::raw(1))
                    ->from('dam_asset_directory')
                    ->whereColumn('dam_asset_directory.asset_id', 'dam_assets.id');

                if (! $globalAssetScope) {
                    $sub->where('dam_asset_directory.directory_id', $dir->id);
                } else {
                    $sub->whereIn('dam_asset_directory.directory_id', $subtreeDirIds);
                }

                if ($directlyGrantedIds !== null) {
                    $sub->whereIn('dam_asset_directory.directory_id', $directlyGrantedIds);
                }
            });

            if ($search) {
                $lowerSearch = '%'.strtolower($search).'%';
                $q->where(function ($w) use ($lowerSearch, $likeOperator) {
                    $w->where('dam_assets.file_name', $likeOperator, $lowerSearch)
                        ->orWhereExists(
                            DB::table('dam_tags')
                                ->join('dam_asset_tag', 'dam_tags.id', '=', 'dam_asset_tag.tag_id')
                                ->whereColumn('dam_asset_tag.asset_id', 'dam_assets.id')
                                ->where('dam_tags.name', $likeOperator, $lowerSearch)
                                ->select(DB::raw(1))
                        );
                });
            }

            if ($filterFileName) {
                $q->where('dam_assets.file_name', $likeOperator, '%'.strtolower($filterFileName).'%');
            }

            if ($filterExtension) {
                $q->where('dam_assets.extension', $likeOperator, '%'.strtolower($filterExtension).'%');
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
                        ->where('dam_tags.name', $likeOperator, $lowerTag)
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
                $q->whereExists(function ($sub) use ($prefix, $propName, $propValue, $likeOperator) {
                    $sub->select(DB::raw(1))
                        ->from('dam_asset_properties')
                        ->whereRaw("{$prefix}dam_asset_properties.dam_asset_id = {$prefix}dam_assets.id")
                        ->where('name', $propName)
                        ->where('value', $likeOperator, '%'.strtolower($propValue).'%');
                });
            }

            return $q;
        };

        $sortColumn = match ($sortBy) {
            'size'       => 'dam_assets.file_size',
            'updated_at' => 'dam_assets.updated_at',
            default      => 'dam_assets.file_name',
        };

        $assetQuery = $buildAssetQuery();
        $totalAssets = (clone $assetQuery)->count('dam_assets.id');

        $assets = $assetQuery
            ->select([
                'dam_assets.id', 'dam_assets.file_name', 'dam_assets.file_type',
                'dam_assets.extension', 'dam_assets.file_size', 'dam_assets.path',
                'dam_assets.mime_type', 'dam_assets.updated_at',
            ])
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
                'directory_id'       => $dir->id,
                'total_assets'       => $totalAssets,
                'total_directories'  => $directories->count(),
                'current_page'       => $page,
                'last_page'          => max(1, (int) ceil($totalAssets / max($perPage, 1))),
                'per_page'           => $perPage,
                'can_access_current' => $this->permissionService->bypass() || $this->permissionService->canAccess($dir->id),
            ],
        ]);
    }

    public function countItems(Request $request): JsonResponse
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.view'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

        $request->validate([
            'asset_ids'       => 'nullable|array',
            'asset_ids.*'     => 'integer|min:1',
            'directory_ids'   => 'nullable|array',
            'directory_ids.*' => 'integer|min:1',
        ]);

        $bypass = $this->permissionService->bypass();
        $grantedIds = $bypass ? null : $this->permissionService->directlyGrantedIds();

        $assetIds = array_unique(array_map('intval', $request->input('asset_ids', [])));
        $dirIds = array_values(array_filter(
            array_unique(array_map('intval', $request->input('directory_ids', []))),
            fn (int $id) => $bypass || $this->permissionService->canAccess($id)
        ));

        $fileCount = count($assetIds);

        if (! empty($dirIds)) {
            $roots = Directory::whereIn('id', $dirIds)->get(['id', '_lft', '_rgt']);

            $subtreeQuery = Directory::query();
            foreach ($roots as $i => $root) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $subtreeQuery->{$method}(function ($q) use ($root) {
                    $q->where('_lft', '>=', $root->_lft)
                        ->where('_rgt', '<=', $root->_rgt);
                });
            }

            if ($grantedIds !== null) {
                $subtreeQuery->whereIn('id', $grantedIds);
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
