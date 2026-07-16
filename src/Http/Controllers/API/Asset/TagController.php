<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Models\Tag;
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

    public function allTags(Request $request)
    {
        $search = trim((string) $request->input('query', ''));

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $query = Tag::query()->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $paginator = $query->paginate($perPage, ['id', 'name']);

        return response()->json([
            'success'      => true,
            'data'         => $paginator->getCollection()
                ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name])
                ->values(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ], 200);
    }

    public function tags(int $id)
    {
        $tags = $this->assetTagRepository->find($id);

        if (! $tags) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.tags.not-found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.tags.found-success'),
            'data'    => $tags,
        ], 200);
    }

    public function addTag(Request $request)
    {
        $request->validate([
            'tag'      => 'required|max:100',
            'asset_id' => 'required|exists:dam_assets,id',
        ]);

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $this->damAuthorizeAsset((int) $assetId);

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

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
            'message' => trans('dam::app.admin.dam.asset.tags.create.create-success'),
            'file'    => $asset,
        ], 201);
    }

    public function removeTag(Request $request)
    {
        $request->validate([
            'tag'      => 'required',
            'asset_id' => 'required|exists:dam_assets,id',
        ]);

        $newTag = $request->get('tag');

        $assetId = $request->get('asset_id');

        $this->damAuthorizeAsset((int) $assetId);

        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $assetTag = $this->assetTagRepository->whereRaw('LOWER(name) = ?', [mb_strtolower($newTag)])->first();

        $oldTags = $asset->tags->pluck('name')->toArray();

        if ($assetTag) {
            $asset->tags()->detach($assetTag->id);

            Event::dispatch('core.model.proxy.sync.tag', [
                'old_values' => $oldTags,
                'new_values' => $asset->refresh()->tags->pluck('name')->toArray(),
                'model'      => $asset,
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.asset.tags.delete-success'),
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.tags.not-found'),
            ], 404);
        }
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'asset_ids'   => 'required|array|min:1|max:500',
            'asset_ids.*' => 'integer|min:1',
            'tags'        => 'required|array|min:1',
            'tags.*'      => 'required|string|max:100',
        ]);

        $names = collect($request->input('tags'))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
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

        $updated = 0;

        foreach ($request->input('asset_ids') as $assetId) {
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

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.mass-action.assign-success', ['count' => $updated]),
            'count'   => $updated,
        ], 200);
    }

    public function destroy(int $id)
    {
        $tag = $this->assetTagRepository->find($id);

        if (! $tag) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.tag.not-found'),
            ], 404);
        }

        $this->assetTagRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.delete-success'),
        ], 200);
    }
}
