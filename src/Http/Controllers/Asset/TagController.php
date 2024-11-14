<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;

class TagController extends Controller
{
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

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'), // asset not found
            ], 404);
        }

        $assetTag = $this->assetTagRepository->where('name', $newTag)->first();

        $oldTags = $asset->tags->pluck('name')->toArray();

        if (! $assetTag) {
            $newTag = $this->assetTagRepository->create(['name' => $newTag]);
            $asset->tags()->attach($newTag->id);
        } else {
            $asset->tags()->attach($assetTag->id);
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

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'), // asset not found
            ], 404);
        }

        $assetTag = $this->assetTagRepository->where('name', $newTag)->first();

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
}
