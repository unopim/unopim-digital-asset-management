<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Http\Requests\DirectoryRequest;
use Webkul\DAM\Jobs\CopyDirectoryStructure as CopyDirectoryStructureJob;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\MoveDirectoryStructure as MoveDirectoryStructureJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use ZipArchive;

class DirectoryController
{
    use ActionRequestTrait;

    public function __construct(protected DirectoryRepository $directoryRepository) {}

    /**
     * Get the directory
     */
    public function index(): JsonResponse
    {
        $directories = $this->directoryRepository->getDirectoryTree();

        return new JsonResponse([
            'data' => $directories,
        ]);
    }

    /**
     * Get the children directory
     */
    public function childrenDirectory(int $id): JsonResponse
    {
        $directory = $this->directoryRepository->getDirectoryTree($id)->first();

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
        $directory = $this->directoryRepository->getDirectoryTree($id)->first();

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

        try {
            $newDirectory = $this->directoryRepository->create([
                'name'      => $request->input('name'),
                'parent_id' => $parentDirectoryId,
            ]);

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
     * Updates a directory
     */
    public function update(DirectoryRequest $request): JsonResponse
    {
        $id = $request->input('id'); // default to root directory

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
            'message' => 'Folder copy successfully.',
            'data'    => null,
        ]);
    }

    /**
     * Copy the directory structure
     */
    public function copyStructure(Request $request): JsonResponse
    {
        $request->validate(
            ['id' =>'required|integer'],
        );

        $copyId = $request->input('id', 1);

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
}
