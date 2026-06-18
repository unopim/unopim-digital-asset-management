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
     * Get the directory
     */
    public function index(Request $request): JsonResponse
    {
        // Callers that need asset nodes in the tree (e.g. the asset picker)
        // must pass `with_assets=1`. The main DAM directory tree only lists
        // folders, so the default skips asset eager-loading for a lighter
        // payload.
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

        // Skip the extra COUNT query when the page is clearly the last one —
        // saves a full-table LIKE scan on every partial page (the common case).
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
     * Lazy-load immediate children of a directory.
     * Each child carries `has_children` and an empty `children` array.
     */
    public function childrenDirectory(int $id): JsonResponse
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

        $children = $this->directoryRepository->getShallowChildren($id);

        return new JsonResponse([
            'data' => $children->values(),
        ]);
    }

    /**
     * Returns the ancestor chain from root to directory $id (inclusive),
     * root-first. Used by the frontend revealDirectory to load a path that
     * is not yet in the locally-loaded lazy tree.
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
     * Get the directory assets
     */
    public function directoryAssets(int $id): JsonResponse
    {
        // DAM_TREE_SHOW_ASSETS env gates the in-tree asset listing. Default
        // off — frontend still uses the right-hand grid for asset browsing
        // on directories with large asset counts.
        if (! config('dam.tree.show_assets')) {
            return new JsonResponse([
                'data' => [],
            ]);
        }

        // Asset listing: strict access (ancestors via expansion don't count).
        if (! $this->permissionService->canAccess($id)) {
            return new JsonResponse([
                'data' => [],
            ]);
        }

        // `getDirectoryTree($id)` returns a single Directory model (or null) when
        // an id is supplied — calling `->first()` on it proxied to a fresh query
        // and silently returned the table's first row, which is the wrong
        // directory. Use the model directly.
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

    /**
     * Create a new directory
     */
    public function store(DirectoryRequest $request)
    {
        $parentDirectoryId = $request->input('parent_id', 1); // default to root directory

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
     * Skipped when the role already bypasses (all-permission or all-directories).
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
     * Updates a directory
     */
    public function update(DirectoryRequest $request): JsonResponse
    {
        $id = $request->input('id'); // default to root directory

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
     * Delete the directory
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
     * Copy the directory
     */
    public function copy(Request $request): JsonResponse
    {
        // @TODO: Need to future enhancement
        // $parentDirectoryId = $request->input('parent_id', 1);
        // $copyId = $request->input('id', 1);

        // $newDirectory = $this->directoryRepository->copy($copyId, $parentDirectoryId);

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.index.directory.copy-success'),
            'data'    => null,
        ]);
    }

    /**
     * Copy the directory structure
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
     * Move the directory one to another location
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

    /**
     * Download archive
     */
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
     * Used by the role-permission editor to resolve breadcrumb chains for
     * all checked directories without N individual /path/{id} calls.
     */
    public function ancestorPaths(Request $request): JsonResponse
    {
        // 'present' ensures the key exists (400-family for missing key)
        // while allowing an empty array. 'required' rejects empty arrays in Laravel.
        $request->validate([
            'ids'   => 'present|array',
            'ids.*' => 'integer|min:1',
        ]);

        $ids = $request->input('ids');

        if (empty($ids)) {
            return new JsonResponse(['data' => []]);
        }

        $nodes = $this->directoryRepository->getAncestorPathsForIds($ids);

        // Note: canView() is based on the editing admin's own grants.
        // A custom-permission admin editing roles may see a filtered ancestor set
        // if they lack access to some parent directories. This is accepted behaviour.
        $nodes = $nodes->filter(fn ($node) => $this->permissionService->canView($node->id));

        return new JsonResponse(['data' => $nodes->values()]);
    }

    /**
     * Returns all descendant IDs for the given directory.
     * Used by the role-permission editor to cascade-select all descendants
     * without requiring the tree to be expanded first.
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
     *
     * Accepts an array of slash-separated relative paths (e.g. "FolderA/SubDir").
     * Each path is walked and any missing segments are created via the repository.
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
