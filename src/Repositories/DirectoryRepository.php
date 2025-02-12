<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Directory;

class DirectoryRepository extends Repository
{
    protected $copyDirectory;

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Directory::class;
    }

    // Method to find a directory with its children
    public function findWithChildren($id)
    {
        return Directory::with('children')->find($id);
    }

    /**
     * Create a new directory
     */
    public function create(array $data)
    {
        $parentDirectory = $this->find($data['parent_id']);

        $this->isDirectoryWritable($parentDirectory, 'create');

        $directory = parent::create($data);
        $newPath = $directory->generatePath();

        $this->createDirectoryWithStorage($newPath);

        return $directory;
    }

    /**
     * Update a directory
     */
    public function update(array $data, $id)
    {
        $oldDirectory = $this->find($id);

        $oldPath = $oldDirectory->generatePath();

        $hasParent = $oldDirectory->parent ? true : false;

        $this->isDirectoryWritable($hasParent ? $oldDirectory->parent : $oldDirectory, 'rename', $hasParent);

        $newDirectory = parent::update($data, $id);

        $newPath = $newDirectory->generatePath();

        if ($oldDirectory->name != $newDirectory->name) {
            $this->createDirectoryWithStorage($newPath, $oldPath);
        }

        return $newDirectory;
    }

    /**
     * Delete a directory
     */
    public function delete($id)
    {
        $directory = $this->find($id);

        $this->isDirectoryWritable($directory, 'delete');

        $path = $directory->generatePath();

        parent::delete($id);

        $this->deleteDirectoryWithStorage($path);
    }

    /**
     * Copy directory
     */
    public function copy($copyId, $parentId)
    {
        $directory = $this->find($copyId);
        $parentDirectory = $this->find($parentId);

        $this->copyWithChildren($directory, $parentId);

        $newDirectory = $this->copyDirectory;

        $this->copyDirectoryWithStorage($parentDirectory->generatePath(), $directory->generatePath());

        return $this->findWithChildren($newDirectory->id);
    }

    /**
     * Copy a directory with children
     */
    public function copyWithChildren($directory, $newParentId = null)
    {
        // Step 1: Replicate the node itself (without its children)
        $childrens = $directory->children()->get();

        // @TODO: Need to improve this

        $newDirectory = $directory->replicate();   // Create a copy of the node
        $newDirectory->parent_id = $newParentId;  // Assign the new parent ID (or set it to null for root)
        $newDirectory->save();  // Save the new node to the database
        if (! $this->copyDirectory) {
            $this->copyDirectory = $newDirectory;
        }

        // Step 2: Recursively copy the children of this node
        foreach ($childrens as $childNode) {
            // For each child node, call the method recursively
            $this->copyWithChildren($childNode, $newDirectory->id);
        }

        return $newDirectory;
    }

    /**
     * Create a directory with storage
     */
    public function createDirectoryWithStorage($newPath, $oldPath = null)
    {
        try {
            $newDirectory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath);

            if (! $oldPath) {
                Storage::disk(Directory::ASSETS_DISK)->makeDirectory($newDirectory);

                return;
            }

            $oldDirectory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldPath);
            // Check if a directory exists
            if (Storage::disk(Directory::ASSETS_DISK)->exists($oldDirectory)) {
                Storage::disk(Directory::ASSETS_DISK)->move($oldDirectory, $newDirectory);
            } else {
                Storage::disk(Directory::ASSETS_DISK)->makeDirectory($newDirectory);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Delete a directory from storage
     */
    public function deleteDirectoryWithStorage($path)
    {
        $directory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $path);

        if (Storage::disk(Directory::ASSETS_DISK)->exists($directory)) {
            Storage::disk(Directory::ASSETS_DISK)->deleteDirectory($directory);
        }
    }

    /**
     * Copy a directory with storage
     */
    public function copyDirectoryWithStorage($newPath, $oldPath)
    {
        $sourcePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldPath);
        $destinationPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath);
        if (Storage::disk(Directory::ASSETS_DISK)->exists($sourcePath)) {

        }
    }

    /**
     * Specify directory tree.
     *
     * @param  int  $id
     * @return \Webkul\DAM\Models\Directory
     */
    public function getDirectoryTree($id = null)
    {
        return $id
            ? $this->model->with(['assets', 'assets.directories', 'children'])->where('id', $id)->first()
            : $this->model->with(['assets', 'assets.directories'])->get()->toTree();
    }

    /**
     * Check if a directory is writable in the file system.
     */
    public function isDirectoryWritable(Directory $directory, string $actionType = 'create', bool $hasParent = true): bool
    {
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $hasParent ? $directory->generatePath() : '');

        if (! $directory->isWritable($directoryPath)) {
            throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                'type'       => 'directory',
                'actionType' => $actionType,
                'path'       => $directoryPath,
            ]));
        }

        return true;
    }
}
