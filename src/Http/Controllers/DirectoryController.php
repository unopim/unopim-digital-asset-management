<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Http\Controllers\Concerns\StreamsZipDownload;
use Webkul\DAM\Http\Requests\DirectoryRequest;
use Webkul\DAM\Http\Requests\DirectorySearchRequest;
use Webkul\DAM\Jobs\CopyDirectoryStructure as CopyDirectoryStructureJob;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\MoveDirectoryStructure as MoveDirectoryStructureJob;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class DirectoryController
{
    use ActionRequestTrait;
    use StreamsZipDownload;

    public function __construct(
        protected DirectoryRepository $directoryRepository,
        protected DirectoryPermissionService $permissionService,
        protected DirectoryRolePermissionRepository $permissionRepository,
    ) {}

    /**
     * Get the directory tree.
     */
    public function index(Request $request): JsonResponse
    {
        $directories = $request->boolean('with_assets')
            ? $this->directoryRepository->getDirectoryTree()
            : $this->directoryRepository->getDirectoryTreeOnly();

        return new JsonResponse([
            'data' => $directories,
        ]);
    }

    /**
     * Substring search across ACL-visible directories.
     */
    public function search(DirectorySearchRequest $request): JsonResponse
    {
        $q = $request->validated('q');
        $limit = 20;
        $offset = (int) ($request->validated('offset') ?? 0);

        $results = $this->directoryRepository->search($q, $limit, $offset);

        $returned = $results->count();
        $total = ($returned < $limit)
            ? $offset + $returned
            : $this->directoryRepository->searchCount($q);

        return new JsonResponse([
            'data' => $results->map(fn ($directory) => [
                'id'         => $directory->id,
                'name'       => $directory->name,
                'parent_id'  => $directory->parent_id,
                'path_names' => $directory->path_names,
                'path_ids'   => $directory->path_ids,
            ])->values(),
            'meta' => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Lazy-load one page of immediate children of a directory.
     */
    public function childrenDirectory(int $id, Request $request): JsonResponse
    {
        if (! $this->permissionService->canView($id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        if (! $this->directoryRepository->find($id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        $offset = max(0, (int) $request->query('offset', 0));
        $limit = (int) $request->query('limit', DirectoryRepository::DEFAULT_TREE_PAGE_SIZE);

        $page = $this->directoryRepository->getShallowChildren($id, null, $offset, $limit);

        return new JsonResponse([
            'data'     => $page['data']->values(),
            'has_more' => $page['has_more'],
        ]);
    }

    /**
     * Lazy asset-count badges for the viewable directory ids.
     */
    public function assetCounts(Request $request): JsonResponse
    {
        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if (! $this->permissionService->bypass()) {
            $viewable = array_flip($this->permissionService->viewableIds());
            $ids = $ids->filter(fn ($id) => isset($viewable[$id]))->values();
        }

        if ($ids->isEmpty()) {
            return new JsonResponse(['data' => (object) []]);
        }

        $allowedDescendantIds = ! $this->permissionService->bypass()
            ? $this->permissionService->directlyGrantedIds()
            : null;

        $counts = $this->directoryRepository->getSubtreeAssetCounts($ids->all(), $allowedDescendantIds);

        return new JsonResponse(['data' => (object) $counts]);
    }

    /**
     * Return the ancestor chain from root to the given directory, root-first.
     */
    public function directoryPath(int $id): JsonResponse
    {
        if (! $this->permissionService->canView($id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $path = $this->directoryRepository->getAncestorPath($id);

        return new JsonResponse([
            'data' => $path->values(),
        ]);
    }

    /**
     * Get the directory assets.
     */
    public function directoryAssets(int $id): JsonResponse
    {
        if (! config('dam.tree.show_assets')) {
            return new JsonResponse([
                'data' => [],
            ]);
        }

        if (! $this->permissionService->canAccess($id)) {
            return new JsonResponse([
                'data' => [],
            ]);
        }

        $directory = $this->directoryRepository->getDirectoryTree($id);

        if (! $directory) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        $assets = $directory->assets;

        return new JsonResponse([
            'data' => $assets,
        ]);
    }

    /** Create a new directory. */
    public function store(DirectoryRequest $request)
    {
        $parentDirectoryId = $request->input('parent_id', 1);

        if (! $this->permissionService->canAccess((int) $parentDirectoryId)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $newDirectory = $this->directoryRepository->create([
                'name'      => $request->input('name'),
                'parent_id' => $parentDirectoryId,
            ]);

            $this->autoGrantToCreator($newDirectory->id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.created-success'),
                'data'    => $newDirectory,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grant the new directory to the creator's role for custom-permission admins.
     */
    private function autoGrantToCreator(int $directoryId): void
    {
        $admin = auth()->guard('admin')->user();

        if (! $admin) {
            return;
        }

        $role = $admin->role;

        if (! $role || $role->permission_type !== 'custom') {
            return;
        }

        if (DB::table('dam_role_settings')->where('role_id', $role->id)->where('all_directories', true)->exists()) {
            return;
        }

        $this->permissionRepository->addDirectoryToRole($role->id, $directoryId);
        $this->permissionService->flush();
    }

    /**
     * Update a directory.
     */
    public function update(DirectoryRequest $request): JsonResponse
    {
        $id = $request->input('id');

        if (! $this->permissionService->canAccess((int) $id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $directory = $this->directoryRepository->find($id);

            if (! $directory) {
                return new JsonResponse([
                    'message' => trans('dam::app.admin.dam.index.directory.not-found'),
                ], 404);
            }

            if ($directory->name !== $request->input('name')) {
                $directory = $this->directoryRepository->update([
                    'name' => $request->input('name'),
                ], $id);

                $this->start(EventType::RENAME_DIRECTORY->value);
                $this->completed(EventType::RENAME_DIRECTORY->value, $this->getUser()->id);
            }

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.updated-success'),
                'data'    => $directory,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the directory.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->permissionService->canAccess($id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->find($id);

        if (! $directory) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        if (! $directory->isDeletable()) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.can-not-deleted'),
            ], 403);
        }

        try {
            $parentDirectory = $directory->parent()->with(['children', 'assets'])->get()?->first();

            $requestAction = $this->start(EventType::DELETE_DIRECTORY->value);

            DeleteDirectoryJob::dispatch($id, $requestAction->getUser()->id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.deleting-in-progress'),
                'data'    => $parentDirectory,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass delete multiple directories.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $ids = $massDestroyRequest->input('indices');

        $requestAction = $this->start(EventType::DELETE_DIRECTORY->value);

        foreach ($ids as $id) {
            if (! $this->permissionService->canAccess($id)) {
                continue;
            }

            $directory = $this->directoryRepository->find($id);

            if (! $directory || ! $directory->isDeletable()) {
                continue;
            }

            DeleteDirectoryJob::dispatch($id, $requestAction->getUser()->id);
        }

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.index.directory.deleting-in-progress'),
        ]);
    }

    /**
     * Copy the directory.
     */
    public function copy(Request $request): JsonResponse
    {

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.index.directory.copy-success'),
            'data'    => null,
        ]);
    }

    /**
     * Copy the directory structure.
     */
    public function copyStructure(Request $request): JsonResponse
    {
        $request->validate(
            ['id' => 'required|integer'],
        );

        $copyId = $request->input('id', 1);

        if (! $this->permissionService->canAccess((int) $copyId)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->find($copyId);

        if (! $directory) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        if (! $directory->isCopyable()) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.can-not-copy'),
            ], 403);
        }

        $requestAction = $this->start(EventType::COPY_DIRECTORY_STRUCTURE->value);

        try {
            CopyDirectoryStructureJob::dispatch($copyId, $requestAction->getUser()->id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.coping-in-progress'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Move the directory from one location to another.
     */
    public function moved(Request $request): JsonResponse
    {
        $request->validate([
            'move_item_id'  => 'required|integer',
            'new_parent_id' => 'required|integer',
        ]);

        $moveId = (int) $request->input('move_item_id');
        $newParentId = (int) $request->input('new_parent_id');

        if (! $this->permissionService->canAccess($moveId)
            || ! $this->permissionService->canAccess($newParentId)
        ) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $requestAction = $this->start(EventType::MOVE_DIRECTORY_STRUCTURE->value);

            MoveDirectoryStructureJob::dispatch($request->input('move_item_id'), $request->input('new_parent_id'), $requestAction->getUser()->id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.moved-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Download the directory subtree as a zip archive. */
    public function downloadArchive(int $id)
    {
        if (! $this->permissionService->canAccess($id)) {
            abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        $directory = $this->directoryRepository->findOrFail($id);

        $folderBase = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());
        $disk = Directory::getAssetDisk();

        $subtreeQuery = Asset::query()
            ->whereHas('directories', fn ($q) => $q->whereBetween('_lft', [$directory->_lft, $directory->_rgt]));

        if (! $subtreeQuery->exists()) {
            return back()->with('error', trans('dam::app.admin.dam.index.directory.empty-directory'));
        }

        $zipName = sprintf('%s.zip', $directory->name);

        return $this->buildZipStreamFromAssets(
            $subtreeQuery->select(['path', 'file_name']),
            $folderBase,
            $disk,
            $zipName,
        );
    }

    /**
     * Return ancestor paths for multiple directory IDs in one request.
     */
    public function ancestorPaths(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'present|array',
            'ids.*' => 'integer|min:1',
        ]);

        $ids = $request->input('ids');

        if (empty($ids)) {
            return new JsonResponse(['data' => []]);
        }

        $nodes = $this->directoryRepository->getAncestorPathsForIds($ids);

        $nodes = $nodes->filter(fn ($node) => $this->permissionService->canView($node->id));

        return new JsonResponse(['data' => $nodes->values()]);
    }

    /**
     * Return all viewable descendant IDs for the given directory.
     */
    public function descendants(int $id): JsonResponse
    {
        $node = $this->directoryRepository->find($id);

        if (! $node || ! $this->permissionService->canView($node->id)) {
            return new JsonResponse(['data' => []]);
        }

        $ids = $this->directoryRepository->getDescendantIds($id);

        $ids = array_values(array_filter($ids, fn ($did) => $this->permissionService->canView($did)));

        return new JsonResponse(['data' => $ids]);
    }

    /**
     * Create an empty directory structure under the given parent directory.
     */
    public function createStructure(Request $request): JsonResponse
    {
        $request->validate([
            'directory_id' => 'required|exists:dam_directories,id',
            'paths'        => 'required|array|min:1',
            'paths.*'      => 'string|max:500',
        ]);

        $directoryId = (int) $request->input('directory_id');
        $paths = $request->input('paths');

        if (! $this->permissionService->canAccess($directoryId)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $forbiddenRe = '/[\\\\\/\:\*\?\"\<\>\|]/u';

        foreach ($paths as $path) {
            $segments = array_filter(explode('/', str_replace('\\', '/', trim((string) $path))), fn ($s) => $s !== '');
            foreach ($segments as $seg) {
                if ($seg === '.' || $seg === '..' || mb_strlen($seg) > 255 || preg_match($forbiddenRe, $seg)) {
                    return response()->json([
                        'success' => false,
                        'message' => trans('dam::app.admin.dam.index.directory.creation-failed'),
                    ], 422);
                }
            }
        }

        $dirCache = [];
        $createdIds = [];

        $resolveOrCreate = function (int $parentId, array $segments) use (&$resolveOrCreate, &$dirCache, &$createdIds): void {
            if (empty($segments)) {
                return;
            }

            $segment = array_shift($segments);
            $cacheKey = $parentId.'/'.$segment;

            if (! isset($dirCache[$cacheKey])) {
                $existing = Directory::where('parent_id', $parentId)
                    ->where('name', $segment)
                    ->first();

                if ($existing) {
                    $dirCache[$cacheKey] = $existing->id;
                } else {
                    $newId = $this->directoryRepository->create(['name' => $segment, 'parent_id' => $parentId])->id;
                    $dirCache[$cacheKey] = $newId;
                    $createdIds[] = $newId;
                }
            }

            $resolveOrCreate($dirCache[$cacheKey], $segments);
        };

        foreach ($paths as $path) {
            $segments = array_values(
                array_filter(explode('/', str_replace('\\', '/', trim((string) $path))), fn ($s) => $s !== '')
            );

            if (! empty($segments)) {
                $resolveOrCreate($directoryId, $segments);
            }
        }

        foreach ($createdIds as $id) {
            $this->autoGrantToCreator($id);
        }

        return response()->json(['success' => true]);
    }
}
