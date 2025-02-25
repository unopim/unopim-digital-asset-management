<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Http\Requests\DirectoryRequest;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class DirectoryController
{
    use ActionRequestTrait;

    public function __construct(protected DirectoryRepository $directoryRepository) {}

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(DirectoryRequest $request)
    {
        $parentDirectoryId = $request->input('parent_id', 1);

        try {
            $newDirectory = $this->directoryRepository->create([
                'name'      => $request->input('name'),
                'parent_id' => $parentDirectoryId,
            ]);

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

    /**
     * Get a directory by its id.
     *
     * @throws ModelNotFoundException If a directory with the given id is not found.
     */
    public function getDirectory(int $id): JsonResponse
    {
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
