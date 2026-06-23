<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
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
     */
    protected function massAssignTags(Request $request)
    {
        $request->validate([
            'indices'   => 'required|array',
            'indices.*' => 'integer',
            'tags'      => 'required|array',
            'tags.*'    => 'required|string|max:100',
        ]);

        if (! bouncer()->hasPermission('dam.asset.update')) {
            abort(401, trans('dam::app.admin.errors.401'));
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

        $updated = 0;

        foreach ($request->input('indices') as $assetId) {
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

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.mass-action.assign-success', ['count' => $updated]),
            'count'   => $updated,
        ]);
    }
}
