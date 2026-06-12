<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;

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
            $disk = Directory::getAssetDisk();

            if (! $oldPath) {
                Storage::disk($disk)->makeDirectory($newDirectory);

                return;
            }

            $oldDirectory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldPath);

            // On object stores like S3 there are no real directories; asset
            // files are moved individually by the caller (see
            // MoveDirectoryStructure::moveAssets), so just clean up the old
            // prefix if anything is left and ensure the new one exists.
            if ($disk === Directory::ASSETS_DISK_AWS) {
                Storage::disk($disk)->deleteDirectory($oldDirectory);
                Storage::disk($disk)->makeDirectory($newDirectory);

                return;
            }

            // Check if a directory exists
            if (Storage::disk($disk)->exists($oldDirectory)) {
                Storage::disk($disk)->move($oldDirectory, $newDirectory);
            } else {
                Storage::disk($disk)->makeDirectory($newDirectory);
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
        $disk = Directory::getAssetDisk();

        if (Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
        }
    }

    /**
     * Copy a directory with storage
     */
    public function copyDirectoryWithStorage($newPath, $oldPath)
    {
        $sourcePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldPath);
        $destinationPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath);
        $disk = Directory::getAssetDisk();
        if (Storage::disk($disk)->exists($sourcePath)) {
        }
    }

    /**
     * Specify directory tree.
     *
     * @param  int  $id
     * @return Directory
     */
    public function getDirectoryTree($id = null)
    {
        $service = app(DirectoryPermissionService::class);
        $applyFilter = ! $service->bypass();
        $allowedIds = $applyFilter ? $service->viewableIds() : null;

        if ($id !== null) {
            if ($applyFilter && ! in_array((int) $id, $allowedIds, true)) {
                return null;
            }

            return $this->model->with(['assets', 'assets.directories', 'children'])
                ->where('id', $id)
                ->first();
        }

        $query = $this->model->with(['assets', 'assets.directories']);

        if ($applyFilter) {
            $query->whereIn('id', $allowedIds);
        }

        $rollup = $applyFilter
            ? $this->getAssetCountsRollup($service->directlyGrantedIds())
            : $this->getAssetCountsRollup();

        return $query->get()
            ->each(fn ($dir) => $dir->assets_total_count = (int) ($rollup[$dir->id] ?? 0))
            ->toTree();
    }

    /**
     * Lazy-load entry point for the main DAM directory tree.
     *
     * Returns root nodes with their direct children pre-loaded (depth 2).
     * Each child carries `has_children: bool` so the UI knows whether to
     * show an expand chevron without fetching deeper levels. Grandchildren
     * (depth 3+) are loaded on demand via `getShallowChildren()` when the
     * user expands a node.
     *
     * Drops the O(n²) `getAssetCountsRollup()` entirely — `assets_count`
     * (direct-only, from `withCount`) is used as `assets_total_count`.
     */
    public function getDirectoryTreeOnly()
    {
        $service = app(DirectoryPermissionService::class);
        $allowedDescendantIds = ! $service->bypass() ? $service->directlyGrantedIds() : null;

        $rootQuery = $this->model->withCount('children')->whereNull('parent_id');

        if (! $service->bypass()) {
            $rootQuery->whereIn('id', $service->viewableIds());
        }

        $roots = $rootQuery->get();

        $rootCounts = $this->getSubtreeAssetCounts($roots->pluck('id')->all(), $allowedDescendantIds);

        foreach ($roots as $root) {
            $root->assets_total_count = $rootCounts[$root->id] ?? 0;
            $root->has_children = $root->children_count > 0;
            $root->children = $this->getShallowChildren($root->id, $service)->values()->all();
        }

        return $roots;
    }

    /**
     * Returns the immediate (depth-1) children of a directory, each stamped
     * with `has_children` (true when they have children of their own) and an
     * empty `children` array so the frontend tree can render the expand chevron
     * without a round-trip.
     */
    public function getShallowChildren(int $parentId, ?DirectoryPermissionService $service = null): Collection
    {
        $service ??= app(DirectoryPermissionService::class);
        $allowedDescendantIds = ! $service->bypass() ? $service->directlyGrantedIds() : null;

        $query = $this->model
            ->withCount('children')
            ->where('parent_id', $parentId)
            ->orderBy('name');

        if (! $service->bypass()) {
            $query->whereIn('id', $service->viewableIds());
        }

        $children = $query->get()->map(function ($dir) {
            $dir->has_children = $dir->children_count > 0;
            $dir->children = [];

            return $dir;
        });

        $counts = $this->getSubtreeAssetCounts($children->pluck('id')->all(), $allowedDescendantIds);

        $children->each(function ($dir) use ($counts) {
            $dir->assets_total_count = $counts[$dir->id] ?? 0;
        });

        return $children;
    }

    /**
     * Returns the ancestor chain from the root down to directory `$id`
     * (inclusive), ordered root-first. Used by `revealDirectory` on the
     * frontend when the target node is not yet in the locally-loaded tree.
     *
     * Each node in the result carries `has_children` but an empty `children`
     * array — the frontend loads each level via `getShallowChildren` as it
     * walks down the path.
     */
    public function getAncestorPath(int $id): Collection
    {
        $target = $this->model->select('id', '_lft', '_rgt', 'parent_id', 'name')->find($id);

        if (! $target) {
            return collect();
        }

        $nodes = $this->model
            ->withCount('children')
            ->where('_lft', '<=', $target->_lft)
            ->where('_rgt', '>=', $target->_rgt)
            ->orderBy('_lft')
            ->get()
            ->map(function ($dir) {
                $dir->has_children = $dir->children_count > 0;
                $dir->children = [];

                return $dir;
            });

        $counts = $this->getSubtreeAssetCounts($nodes->pluck('id')->all());

        $nodes->each(function ($dir) use ($counts) {
            $dir->assets_total_count = $counts[$dir->id] ?? 0;
        });

        return $nodes;
    }

    /**
     * Recursive asset count for each of the given directory IDs in one query.
     * Counts assets in the directory itself plus all descendants via nested-set range.
     * Returns [id => count]. IDs absent from the result had zero assets.
     *
     * When `$allowedDescendantIds` is provided, only descendants whose id is in
     * that list contribute to the count (ACL-filtered rollup for the lazy tree).
     *
     * @param  array<int>  $ids
     * @param  array<int>|null  $allowedDescendantIds  null = count all descendants
     * @return array<int, int>
     */
    public function getSubtreeAssetCounts(array $ids, ?array $allowedDescendantIds = null): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        if (empty($ids)) {
            return [];
        }

        if ($allowedDescendantIds !== null && empty($allowedDescendantIds)) {
            return array_fill_keys($ids, 0);
        }

        $prefix = DB::getTablePrefix();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $descendantFilter = '';
        $bindings = $ids;

        if ($allowedDescendantIds !== null) {
            $dPlaceholders = implode(',', array_fill(0, count($allowedDescendantIds), '?'));
            $descendantFilter = "AND descendant.id IN ({$dPlaceholders})";
            // ON-clause bindings come before WHERE-clause bindings
            $bindings = array_merge($allowedDescendantIds, $ids);
        }

        $rows = DB::select("
            SELECT ancestor.id AS id, COUNT(DISTINCT ad.asset_id) AS total
            FROM {$prefix}dam_directories AS ancestor
            LEFT JOIN {$prefix}dam_directories AS descendant
                ON descendant._lft >= ancestor._lft AND descendant._rgt <= ancestor._rgt
                {$descendantFilter}
            LEFT JOIN {$prefix}dam_asset_directory AS ad
                ON ad.directory_id = descendant.id
            WHERE ancestor.id IN ({$placeholders})
            GROUP BY ancestor.id
        ", $bindings);

        return collect($rows)
            ->mapWithKeys(fn ($row) => [(int) $row->id => (int) $row->total])
            ->all();
    }

    /**
     * Full directory tree without ACL filtering. Used by the directory
     * permission manager UI, which must always show every directory so an
     * admin can grant access to any of them. Callers must enforce their own
     * authorization before invoking this.
     */
    public function getFullDirectoryTreeOnly()
    {
        $rollup = $this->getAssetCountsRollup();

        return $this->model->withCount('assets')
            ->get()
            ->each(fn ($dir) => $dir->assets_total_count = (int) ($rollup[$dir->id] ?? 0))
            ->toTree();
    }

    /**
     * Recursive asset-count rollup per directory using the nested-set
     * `_lft`/`_rgt` columns. Returns `[directory_id => total]` where total
     * counts distinct assets attached anywhere in the subtree rooted at
     * the directory (own + every descendant).
     *
     * When `$allowedDirectoryIds` is provided, only descendants whose id is in
     * that list contribute to the count. Pass `directlyGrantedIds()` here when
     * rendering a permission-filtered tree so ancestor nodes only reflect assets
     * from directories the current role has been explicitly granted.
     *
     * Single query, portable across MySQL + PostgreSQL — no driver-specific
     * syntax, no raw table names, prefix-aware via the query builder.
     *
     * @param  array<int>|null  $allowedDirectoryIds  null = count all descendants
     * @return array<int, int>
     */
    public function getAssetCountsRollup(?array $allowedDirectoryIds = null): array
    {
        // Raw SQL because Laravel's query builder prefixes table aliases too
        // (e.g. `as d` → `prefix_d`) which then mismatches alias references
        // in subsequent column expressions on Postgres. Composing the SQL
        // ourselves with `DB::getTablePrefix()` keeps the joins portable
        // across MySQL + Postgres and works with any prefix configuration.
        $prefix = DB::getTablePrefix();

        if ($allowedDirectoryIds !== null && empty($allowedDirectoryIds)) {
            // Role has no grants at all — every directory gets a zero count.
            $rows = DB::select("SELECT id FROM {$prefix}dam_directories");

            return collect($rows)
                ->mapWithKeys(fn ($row) => [(int) $row->id => 0])
                ->all();
        }

        $descendantFilter = '';
        $bindings = [];

        if ($allowedDirectoryIds !== null) {
            $placeholders = implode(',', array_fill(0, count($allowedDirectoryIds), '?'));
            $descendantFilter = "AND descendant.id IN ({$placeholders})";
            $bindings = $allowedDirectoryIds;
        }

        $rows = DB::select("
            SELECT ancestor.id AS id, COUNT(DISTINCT ad.asset_id) AS total
            FROM {$prefix}dam_directories AS ancestor
            LEFT JOIN {$prefix}dam_directories AS descendant
                ON descendant._lft >= ancestor._lft
                AND descendant._rgt <= ancestor._rgt
                {$descendantFilter}
            LEFT JOIN {$prefix}dam_asset_directory AS ad
                ON ad.directory_id = descendant.id
            GROUP BY ancestor.id
        ", $bindings);

        return collect($rows)
            ->mapWithKeys(fn ($row) => [(int) $row->id => (int) $row->total])
            ->all();
    }

    /**
     * Substring directory search filtered by ACL visibility.
     *
     * @return Collection
     */
    public function search(string $query, int $limit = 20, int $offset = 0)
    {
        $builder = $this->buildSearchQuery($query);

        if ($builder === null) {
            return collect();
        }

        $matches = $builder
            ->orderBy('name')
            ->orderBy('id')
            ->offset(max(0, $offset))
            ->limit($limit)
            ->get(['id', 'name', 'parent_id', '_lft', '_rgt']);

        return $matches->map(function ($directory) {
            $directory->path_names = $this->resolveAncestorPathNames($directory);

            return $directory;
        })->values();
    }

    /**
     * Total ACL-filtered match count for a directory search query.
     */
    public function searchCount(string $query): int
    {
        $builder = $this->buildSearchQuery($query);

        return $builder === null ? 0 : $builder->count();
    }

    protected function buildSearchQuery(string $query)
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return null;
        }

        $service = app(DirectoryPermissionService::class);

        $builder = $this->model->newQuery()
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($query).'%']);

        if (! $service->bypass()) {
            $builder->whereIn('id', $service->viewableIds());
        }

        return $builder;
    }

    /**
     * Resolve top-down ancestor name chain (Root, ..., target) for a directory
     * using the nested-set _lft/_rgt columns.
     */
    protected function resolveAncestorPathNames($directory): array
    {
        $ancestors = $this->model->newQuery()
            ->where('_lft', '<', $directory->_lft)
            ->where('_rgt', '>', $directory->_rgt)
            ->orderBy('_lft')
            ->pluck('name')
            ->all();

        $ancestors[] = $directory->name;

        return $ancestors;
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
