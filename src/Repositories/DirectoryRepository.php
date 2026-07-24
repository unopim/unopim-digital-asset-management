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

    public const DEFAULT_TREE_PAGE_SIZE = 100;

    protected $copyDirectory;

    public function model(): string
    {
        return Directory::class;
    }

    public function findWithChildren($id)
    {
        return Directory::with('children')->find($id);
    }

    public function create(array $data)
    {
        $parentDirectory = $this->find($data['parent_id']);

        $this->isDirectoryWritable($parentDirectory, 'create');

        $directory = parent::create($data);
        $newPath = $directory->generatePath();

        $this->createDirectoryWithStorage($newPath);

        return $directory;
    }

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

            $oldPrefix = Directory::ASSETS_DIRECTORY.'/'.$oldPath.'/';
            $newPrefix = Directory::ASSETS_DIRECTORY.'/'.$newPath.'/';

            DB::statement(
                sprintf('UPDATE %sdam_assets SET path = REPLACE(path, ?, ?) WHERE path LIKE ?', DB::getTablePrefix()),
                [$oldPrefix, $newPrefix, $oldPrefix.'%']
            );
        }

        return $newDirectory;
    }

    public function delete($id)
    {
        $directory = $this->find($id);

        $this->isDirectoryWritable($directory, 'delete');

        $path = $directory->generatePath();

        parent::delete($id);

        $this->deleteDirectoryWithStorage($path);
    }

    public function copy($copyId, $parentId)
    {
        $directory = $this->find($copyId);
        $parentDirectory = $this->find($parentId);

        $this->copyWithChildren($directory, $parentId);

        $newDirectory = $this->copyDirectory;

        $this->copyDirectoryWithStorage($parentDirectory->generatePath(), $directory->generatePath());

        return $this->findWithChildren($newDirectory->id);
    }

    public function copyWithChildren($directory, $newParentId = null)
    {
        $childrens = $directory->children()->get();

        $newDirectory = $directory->replicate();
        $newDirectory->parent_id = $newParentId;
        $newDirectory->save();
        if (! $this->copyDirectory) {
            $this->copyDirectory = $newDirectory;
        }

        foreach ($childrens as $childNode) {
            $this->copyWithChildren($childNode, $newDirectory->id);
        }

        return $newDirectory;
    }

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

            if ($disk === Directory::ASSETS_DISK_AWS) {
                Storage::disk($disk)->deleteDirectory($oldDirectory);
                Storage::disk($disk)->makeDirectory($newDirectory);

                return;
            }

            if (Storage::disk($disk)->exists($oldDirectory)) {
                Storage::disk($disk)->move($oldDirectory, $newDirectory);
            } else {
                Storage::disk($disk)->makeDirectory($newDirectory);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function deleteDirectoryWithStorage($path)
    {
        $directory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $path);
        $disk = Directory::getAssetDisk();

        if (Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->deleteDirectory($directory);
        }
    }

    public function copyDirectoryWithStorage($newPath, $oldPath)
    {
        $sourcePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldPath);
        $destinationPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath);
        $disk = Directory::getAssetDisk();
        if (Storage::disk($disk)->exists($sourcePath)) {
        }
    }

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

    public function getDirectoryTreeOnly()
    {
        $service = app(DirectoryPermissionService::class);

        $rootQuery = $this->model->withCount('children')->whereNull('parent_id');

        if (! $service->bypass()) {
            $rootQuery->whereIn('id', $service->viewableIds());
        }

        $roots = $rootQuery->get();

        foreach ($roots as $root) {
            $root->has_children = $root->children_count > 0;

            $page = $this->getShallowChildren($root->id, $service);
            $root->children = $page['data']->all();
            $root->children_has_more = $page['has_more'];
        }

        return $roots;
    }

    public function getShallowChildren(int $parentId, ?DirectoryPermissionService $service = null, int $offset = 0, int $limit = self::DEFAULT_TREE_PAGE_SIZE): array
    {
        $service ??= app(DirectoryPermissionService::class);

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $query = $this->model
            ->withCount('children')
            ->where('parent_id', $parentId)
            ->orderBy('name');

        if (! $service->bypass()) {
            $query->whereIn('id', $service->viewableIds());
        }

        $rows = $query->skip($offset)->take($limit + 1)->get();

        $hasMore = $rows->count() > $limit;

        $children = $rows->take($limit)->map(function ($dir) {
            $dir->has_children = $dir->children_count > 0;
            $dir->children = [];

            return $dir;
        })->values();

        return [
            'data'     => $children,
            'has_more' => $hasMore,
        ];
    }

    public function getAncestorPath(int $id): Collection
    {
        $target = $this->model->select('id', '_lft', '_rgt', 'parent_id', 'name')->find($id);

        if (! $target) {
            return collect();
        }

        return $this->model
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
    }

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

    public function getFullDirectoryTreeOnly()
    {
        $rollup = $this->getAssetCountsRollup();

        return $this->model->withCount('assets')
            ->get()
            ->each(fn ($dir) => $dir->assets_total_count = (int) ($rollup[$dir->id] ?? 0))
            ->toTree();
    }

    public function getAncestorPathsForIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => (int) $id > 0)));

        if (empty($ids)) {
            return collect();
        }

        $prefix = DB::getTablePrefix();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::select("
            SELECT DISTINCT ancestor.id, ancestor._lft
            FROM {$prefix}dam_directories AS ancestor
            INNER JOIN {$prefix}dam_directories AS descendant
                ON ancestor._lft <= descendant._lft
                AND ancestor._rgt >= descendant._rgt
            WHERE descendant.id IN ({$placeholders})
            ORDER BY ancestor._lft
        ", $ids);

        $ancestorIds = collect($rows)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($ancestorIds)) {
            return collect();
        }

        return $this->model
            ->withCount('children')
            ->whereIn('id', $ancestorIds)
            ->orderBy('_lft')
            ->get()
            ->map(function ($dir) {
                $dir->has_children = $dir->children_count > 0;
                $dir->assets_total_count = 0;
                $dir->children = [];

                return $dir;
            });
    }

    public function getDescendantIds(int $id): array
    {
        $node = $this->model->where('id', $id)->first(['_lft', '_rgt']);

        if (! $node) {
            return [];
        }

        return $this->model
            ->where('_lft', '>', $node->_lft)
            ->where('_rgt', '<', $node->_rgt)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function getAssetCountsRollup(?array $allowedDirectoryIds = null): array
    {
        $prefix = DB::getTablePrefix();

        if ($allowedDirectoryIds !== null && empty($allowedDirectoryIds)) {
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

        return $this->attachAncestorPaths($matches);
    }

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

    protected function attachAncestorPaths($directories)
    {
        if ($directories->isEmpty()) {
            return $directories->values();
        }

        $table = $this->model->getTable();
        $ids = $directories->pluck('id')->all();

        $rows = DB::table("{$table} as anc")
            ->join("{$table} as child", function ($join) {
                $join->whereColumn('anc._lft', '<=', 'child._lft')
                    ->whereColumn('anc._rgt', '>=', 'child._rgt');
            })
            ->whereIn('child.id', $ids)
            ->orderBy('child.id')
            ->orderBy('anc._lft')
            ->select('anc.id as anc_id', 'anc.name as anc_name', 'child.id as for_id')
            ->get()
            ->groupBy('for_id');

        return $directories->map(function ($directory) use ($rows) {
            $ancestors = $rows->get($directory->id, collect());
            $directory->path_names = $ancestors->pluck('anc_name')->all();
            $directory->path_ids = $ancestors->pluck('anc_id')->all();

            return $directory;
        })->values();
    }

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
