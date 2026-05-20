<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Http\Requests\DirectoryRequest;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class DirectoryController
{
    use ActionRequestTrait;

    public function __construct(
        protected DirectoryRepository $directoryRepository,
        protected DirectoryPermissionService $permissionService,
        protected DirectoryRolePermissionRepository $permissionRepository,
    ) {}

    /**
     * Get all the directory.
     */
    public function index(): JsonResponse
    {
        $directories = $this->directoryRepository->getDirectoryTree();

        return new JsonResponse([
            'success' => true,
            'message' => trans('dam::app.admin.dam.index.directory.fetch-all-success'),
            'data'    => $directories,
        ]);
    }

    /**
     * Store a newly created directory.
     */
    public function store(DirectoryRequest $request): JsonResponse
    {
        $parentDirectoryId = $request->input('parent_id', 1);

        if (! $this->permissionService->canAccess((int) $parentDirectoryId)) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $newDirectory = $this->directoryRepository->create([
                'name'      => $request->input('name'),
                'parent_id' => $parentDirectoryId,
            ]);

            $this->autoGrantToCreator($newDirectory->id);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.index.directory.created-success'),
                'data'    => $newDirectory,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.creation-failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function autoGrantToCreator(int $directoryId): void
    {
        $admin = auth()->guard('admin')->user() ?? auth()->guard('api')->user();

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
     * Get a directory by its id.
     *
     * @throws ModelNotFoundException If a directory with the given id is not found.
     */
    public function getDirectory(int $id): JsonResponse
    {
        if (! $this->permissionService->canView($id)) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->getDirectoryTree($id);
        if (! $directory) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $directory,
        ], 200);
    }

    /**
     * Update the specified directory.
     */
    public function update(DirectoryRequest $request, int $id): JsonResponse
    {
        if (! $this->permissionService->canAccess($id)) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $directory = $this->directoryRepository->find($id);

            if (! $directory) {
                return response()->json([
                    'success' => false,
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

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.index.directory.updated-success'),
                'data'    => $directory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.update-failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the specified directory.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->permissionService->canAccess($id)) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->find($id);

        if (! $directory) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.not-found'),
            ], 404);
        }

        if (! $directory->isDeletable()) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.can-not-deleted'),
            ], 403);
        }

        try {
            $parentDirectory = $directory->parent()->with(['children', 'assets'])->get()?->first();

            $requestAction = $this->start(EventType::DELETE_DIRECTORY->value);

            DeleteDirectoryJob::dispatch($id, $requestAction->getUser()->id);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.index.directory.deleting-in-progress'),
                'data'    => $parentDirectory,
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
