<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Tag\TagDataGrid;
use Webkul\DAM\Http\Requests\TagRequest;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Repositories\AssetTagRepository;

class TagController extends Controller
{
    public function __construct(
        protected AssetTagRepository $tagRepository,
    ) {}

    /**
     * Tag management listing page (and its datagrid JSON feed).
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(TagDataGrid::class)->toJson();
        }

        return view('dam::tag.index');
    }

    /**
     * Create a new tag.
     */
    public function store(TagRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('dam.tags.create')) {
            return $this->unauthorized();
        }

        $tag = $this->tagRepository->create(['name' => trim($request->input('name'))]);

        return response()->json([
            'success' => true,
            'tag'     => ['id' => $tag->id, 'name' => $tag->name],
            'message' => trans('dam::app.admin.dam.tag.create-success'),
        ]);
    }

    /**
     * Rename an existing tag. The pivot rows reference the tag id, so a rename
     * transparently updates the label everywhere the tag is used.
     */
    public function update(TagRequest $request, int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('dam.tags.update')) {
            return $this->unauthorized();
        }

        $tag = $this->tagRepository->find($id);

        if (! $tag) {
            return $this->notFound();
        }

        $this->tagRepository->update(['name' => trim($request->input('name'))], $id);

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.update-success'),
        ]);
    }

    /**
     * Delete a single tag. The dam_asset_tag pivot rows are removed by the
     * tag_id foreign key's ON DELETE CASCADE.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('dam.tags.delete')) {
            return $this->unauthorized();
        }

        $tag = $this->tagRepository->find($id);

        if (! $tag) {
            return $this->notFound();
        }

        $this->tagRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.delete-success'),
        ]);
    }

    /**
     * Delete many tags at once (datagrid mass action).
     */
    public function massDestroy(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('dam.tags.delete')) {
            return $this->unauthorized();
        }

        $request->validate([
            'indices'   => 'required|array',
            'indices.*' => 'integer',
        ]);

        Tag::whereIn('id', $request->input('indices'))->delete();

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.tag.mass-delete-success'),
        ]);
    }

    /**
     * Lightweight tag list used to populate the "Assign Tags" autocomplete.
     */
    public function list(): JsonResponse
    {
        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name]);

        return response()->json(['data' => $tags]);
    }

    protected function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => trans('dam::app.admin.permissions.unauthorized'),
        ], 403);
    }

    protected function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => trans('dam::app.admin.dam.tag.not-found'),
        ], 404);
    }
}
