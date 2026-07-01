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

    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
    ) {}

    /**
     * To add and update the asset tag
     */
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
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'), // asset not found
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

    /**
     * To remove the asset tag
     */
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
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'), // asset not found
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

    /**
     * Attach one or more tags to many assets at once (datagrid + explorer mass action).
     *
     * Additive: existing tags on each asset are kept. Tag names are resolved
     * find-or-create (case-insensitive) so the same payload works whether the
     * tag already exists or is being created on the fly.
     *
     * Two target sources, which may be combined:
     *  - `indices`: explicitly selected asset ids (small, bounded by the page) — tagged
     *    one-by-one so each fires the history sync event.
     *  - `directory_ids`: selected folders. Every asset inside them, recursively through
     *    all sub-folders, is tagged via a single set-based INSERT ... SELECT per tag, so
     *    it stays fast even for folders holding millions of assets (no rows loaded into
     *    PHP, no per-asset history event).
     */
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

        // Normalise + de-duplicate the submitted names (trimmed, case-insensitive).
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

        // Resolve every name to a tag id once — reused across all selected assets.
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
     *
     * @return int number of assets tagged
     */
    protected function tagSelectedAssets(array $assetIds, array $tagIds): int
    {
        $updated = 0;

        foreach ($assetIds as $assetId) {
            // Silently skip assets the current user may not touch — never leak their existence.
            if (! $this->damCanAccessAsset((int) $assetId)) {
                continue;
            }

            $asset = $this->assetRepository->find($assetId);

            if (! $asset) {
                continue;
            }

            $oldTags = $asset->tags->pluck('name')->toArray();

            // Keeps existing tags and ignores already-attached ones — no duplicate pivot rows.
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
     * Recursively tag every asset contained in the given folders (and all sub-folders).
     *
     * Set-based and bounded: the descendant directory ids are resolved inside SQL via the
     * nested-set lft/rgt range, and a single INSERT ... SELECT (ignoring duplicates) per tag
     * writes the pivot rows — so the cost is a constant number of queries regardless of how
     * many assets the folders hold. No per-asset history event is emitted on this path.
     *
     * @return int number of distinct assets the folders contain
     */
    protected function tagAssetsInDirectories(array $directoryIds, array $tagIds): int
    {
        // Only act on folders the user may access (a folder they selected). Access to a
        // folder implies its whole subtree, mirroring the copy/move mass actions.
        // bypass() is resolved once so selecting many folders stays cheap.
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

        // Resolves the descendant directory ids entirely in SQL (one lft/rgt range per
        // selected folder, OR-ed together) — never plucked into PHP, so memory stays flat.
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

        // The timestamp columns are selected as literals in an INSERT...SELECT.
        // MySQL implicitly casts the string literal to its timestamp column type,
        // but PostgreSQL rejects text→timestamp, so cast explicitly there.
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
