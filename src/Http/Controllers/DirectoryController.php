<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Http\Requests\DirectoryRequest;
use Webkul\DAM\Http\Requests\DirectorySearchRequest;
use Webkul\DAM\Jobs\CopyDirectoryStructure as CopyDirectoryStructureJob;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\MoveDirectoryStructure as MoveDirectoryStructureJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use ZipArchive;

class DirectoryController
{
    use ActionRequestTrait;

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
        $total = $this->directoryRepository->searchCount($q);

        return new JsonResponse([
            'data' => $results->map(fn ($directory) => [
                'id'         => $directory->id,
                'name'       => $directory->name,
                'parent_id'  => $directory->parent_id,
                'path_names' => $directory->path_names,
            ])->values(),
            'meta' => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Get the children directory
     */
    public function childrenDirectory(int $id): JsonResponse
    {
        if (! $this->permissionService->canView($id)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->getDirectoryTree($id)?->first();

        if (! $directory) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        return new JsonResponse([
            'data' => $directory,
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

                $requestAction = $this->start(EventType::RENAME_DIRECTORY->value);

                RenameDirectoryJob::dispatch($id, $requestAction->getUser()->id);
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

        $folderPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());
        $disk = Directory::getAssetDisk();
        $files = Storage::disk($disk)->allFiles($folderPath);
        $directories = Storage::disk($disk)->allDirectories($folderPath);

        if (empty($directories) && empty($files)) {
            return back()->with('error', trans('dam::app.admin.dam.index.directory.empty-directory'));
        }

        $zip = new ZipArchive;
        $zipFileName = sprintf('%s.zip', $directory->name);
        if ($zip->open(public_path($zipFileName), ZipArchive::CREATE) === true) {
            // Add files to the ZIP archive
            foreach ($files as $file) {
                $relativePath = str_replace($folderPath.'/', '', $file);
                $fileContents = Storage::disk($disk)->get($file);
                $zip->addFromString($relativePath, $fileContents);
            }

            // Add directories to the ZIP archive
            foreach ($directories as $directory) {
                $relativePath = str_replace($folderPath.'/', '', $directory);
                $zip->addEmptyDir($relativePath);
            }

            $zip->close();

            return response()->download(public_path($zipFileName))->deleteFileAfterSend(true);
        } else {
            return back()->with('error', trans('dam::app.admin.dam.index.directory.failed-download-directory'));
        }
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

        $dirCache = [];

        $resolveOrCreate = function (int $parentId, array $segments) use (&$resolveOrCreate, &$dirCache): void {
            if (empty($segments)) {
                return;
            }

            $segment = array_shift($segments);
            $cacheKey = $parentId.'/'.$segment;

            if (! isset($dirCache[$cacheKey])) {
                $existing = Directory::where('parent_id', $parentId)
                    ->where('name', $segment)
                    ->first();

                $dirCache[$cacheKey] = $existing
                    ? $existing->id
                    : $this->directoryRepository->create(['name' => $segment, 'parent_id' => $parentId])->id;
            }

            $resolveOrCreate($dirCache[$cacheKey], $segments);
        };

        foreach ($paths as $path) {
            $segments = array_values(
                array_filter(explode('/', str_replace('\\', '/', trim((string) $path))))
            );

            if (! empty($segments)) {
                $resolveOrCreate($directoryId, $segments);
            }
        }

        return response()->json(['success' => true]);
    }
}
