<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Traits\AssetAccessControl;

class TagController extends Controller
{
    use AssetAccessControl;

    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
    ) {}

    /** Add and update the asset tag. */
    protected function addOrUpdateTag(Request $request)
    {
        $request->validate([
            'tag'      => 'required|max:100',
            'asset_id' => 'required|exists:dam_assets,id',
        ]);

        if (! bouncer()->hasPermission('dam.asset.update')) {
            abort(401, trans('dam::app.admin.errors.401'));
        }

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $this->damAuthorizeAsset((int) $assetId);

        $assetTag = $this->assetTagRepository->whereRaw('LOWER(name) = ?', [mb_strtolower($newTag)])->first();

        $oldTags = $asset->tags->pluck('name')->toArray();

        if ($assetTag) {
            $existingAssetTagIds = $asset->tags->pluck('id')->toArray();

            if (in_array($assetTag->id, $existingAssetTagIds)) {
                return response()->json([
                    'success' => false,
                    'file'    => $asset,
                    'message' => trans('dam::app.admin.dam.asset.edit.tag-already-exists'),
                ], 404);
            }

            $asset->tags()->attach($assetTag->id);
        } else {
            $newTag = $this->assetTagRepository->create(['name' => $newTag]);
            $asset->tags()->attach($newTag->id);
        }

        Event::dispatch('core.model.proxy.sync.tag', [
            'old_values' => $oldTags,
            'new_values' => $asset->refresh()->tags->pluck('name')->toArray(),
            'model'      => $asset,
        ]);

        return response()->json([
            'success' => true,
            'file'    => $asset,
            'message' => trans('Tag attached successfully'),
        ], 201);
    }

    /** Remove the asset tag. */
    protected function removeTag(Request $request)
    {
        $request->validate([
            'tag'      => 'required',
            'asset_id' => 'required|exists:dam_assets,id',
        ]);

        if (! bouncer()->hasPermission('dam.asset.update')) {
            abort(401, trans('dam::app.admin.errors.401'));
        }

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $this->damAuthorizeAsset((int) $assetId);

        $assetTag = $this->assetTagRepository->whereRaw('LOWER(name) = ?', [mb_strtolower($newTag)])->first();

        $oldTags = $asset->tags->pluck('name')->toArray();

        if ($assetTag) {
            $asset->tags()->detach($assetTag->id);

            Event::dispatch('core.model.proxy.sync.tag', [
                'old_values' => $oldTags,
                'new_values' => $asset->refresh()->tags->pluck('name')->toArray(),
                'model'      => $asset,
            ]);
        }

        return response()->json([
            'success' => true,
            'file'    => $asset,
            'message' => trans('Tag removed from asset successfully'),
        ], 201);
    }

    /** Attach one or more tags to many assets at once. */
    protected function massAssignTags(Request $request)
    {
        $request->validate([
            'indices'         => 'nullable|array',
            'indices.*'       => 'integer',
            'directory_ids'   => 'nullable|array',
            'directory_ids.*' => 'integer',
            'tags'            => 'required|array',
            'tags.*'          => 'required|string|max:100',
        ]);

        if (! bouncer()->hasPermission('dam.asset.update')) {
            abort(401, trans('dam::app.admin.errors.401'));
        }

        if (empty($request->input('indices')) && empty($request->input('directory_ids'))) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.tag.mass-action.no-items'),
            ], 422);
        }

        $names = collect($request->input('tags'))
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique(fn ($t) => mb_strtolower($t))
            ->values();

        if ($names->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.tag.mass-action.no-tags'),
            ], 422);
        }

        $tagIds = $names->map(function ($name) {
            $tag = $this->assetTagRepository->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

            return $tag?->id ?? $this->assetTagRepository->create(['name' => $name])->id;
        })->all();

        $updated = $this->tagSelectedAssets($request->input('indices', []), $tagIds);

        $updated += $this->tagAssetsInDirectories($request->input('directory_ids', []), $tagIds);

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.mass-action.assign-success', ['count' => $updated]),
            'count'   => $updated,
        ]);
    }

    /**
     * Tag explicitly selected assets one at a time so each emits the history sync event.
     */
    protected function tagSelectedAssets(array $assetIds, array $tagIds): int
    {
        $updated = 0;

        foreach ($assetIds as $assetId) {
            if (! $this->damCanAccessAsset((int) $assetId)) {
                continue;
            }

            $asset = $this->assetRepository->find($assetId);

            if (! $asset) {
                continue;
            }

            $oldTags = $asset->tags->pluck('name')->toArray();

            $asset->tags()->syncWithoutDetaching($tagIds);

            Event::dispatch('core.model.proxy.sync.tag', [
                'old_values' => $oldTags,
                'new_values' => $asset->refresh()->tags->pluck('name')->toArray(),
                'model'      => $asset,
            ]);

            $updated++;
        }

        return $updated;
    }

    /**
     * Recursively tag every asset contained in the given folders.
     */
    protected function tagAssetsInDirectories(array $directoryIds, array $tagIds): int
    {
        $service = $this->damPermissionService();
        $bypass = $service->bypass();

        $directoryIds = array_values(array_filter(
            array_unique(array_map('intval', $directoryIds)),
            fn (int $id) => $bypass || $service->canView($id)
        ));

        if (empty($directoryIds)) {
            return 0;
        }

        $roots = Directory::whereIn('id', $directoryIds)->get(['id', '_lft', '_rgt']);

        if ($roots->isEmpty()) {
            return 0;
        }

        $dirTable = (new Directory)->getTable();
        $now = now()->toDateTimeString();

        $subtreeDirIds = function ($query) use ($roots, $dirTable) {
            $query->select('id')->from($dirTable)->where(function ($scope) use ($roots) {
                foreach ($roots as $i => $root) {
                    $scope->{$i === 0 ? 'where' : 'orWhere'}(function ($range) use ($root) {
                        $range->where('_lft', '>=', $root->_lft)
                            ->where('_rgt', '<=', $root->_rgt);
                    });
                }
            });
        };

        $nowLiteral = DB::getPdo()->quote($now);
        $nowExpr = DB::connection()->getDriverName() === 'pgsql'
            ? "CAST($nowLiteral AS timestamp)"
            : $nowLiteral;

        foreach ($tagIds as $tagId) {
            DB::table('dam_asset_tag')->insertOrIgnoreUsing(
                ['asset_id', 'tag_id', 'created_at', 'updated_at'],
                DB::table('dam_asset_directory')
                    ->whereIn('directory_id', $subtreeDirIds)
                    ->distinct()
                    ->select(
                        'asset_id',
                        DB::raw((int) $tagId.' as tag_id'),
                        DB::raw($nowExpr.' as created_at'),
                        DB::raw($nowExpr.' as updated_at'),
                    )
            );
        }

        return DB::table('dam_asset_directory')
            ->whereIn('directory_id', $subtreeDirIds)
            ->distinct()
            ->count('asset_id');
    }
}
