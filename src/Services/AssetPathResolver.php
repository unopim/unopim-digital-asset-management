<?php

namespace Webkul\DAM\Services;

use Webkul\DAM\Exceptions\DirectoryAccessDeniedException;
use Webkul\DAM\Exceptions\InvalidPathException;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;

class AssetPathResolver
{
    protected const FORBIDDEN_SEGMENT = '/[\\\\\/\:\*\?\"\<\>\|]/u';

    protected array $cache = [];

    /**
     * Ids of directories this resolver created during the current run. A freshly
     * created directory is writable by whoever created it (its parent was already
     * permission-checked), so the leaf write-check skips these.
     *
     * @var array<int, true>
     */
    protected array $createdDirectoryIds = [];

    protected ?Directory $baseDirectory = null;

    protected bool $baseResolved = false;

    public function __construct(
        protected DirectoryRepository $directoryRepository,
        protected DirectoryPermissionService $permissionService,
    ) {}

    public function resolveDirectoryForPath(string $damPath): int
    {
        $segments = $this->directorySegments($damPath);

        if ($segments === []) {
            throw new InvalidPathException('Asset path is missing the root anchor: '.$damPath);
        }

        return $this->resolveSegments($segments);
    }

    /**
     * Resolve a directory tree path (e.g. "Root/Brand/Summer") to the id of its
     * leaf directory, creating missing segments along the way. Unlike matching a
     * bare directory name, this anchors the lookup by full path so duplicate names
     * in different branches are unambiguous.
     */
    public function resolveDirectory(string $treePath): int
    {
        $segments = $this->splitAndValidate($this->stripPrefix($treePath));

        if ($segments === []) {
            throw new InvalidPathException('Directory path is empty: '.$treePath);
        }

        return $this->resolveSegments($segments);
    }

    /**
     * Whether the given directory id was created during this run.
     */
    public function wasCreated(int $directoryId): bool
    {
        return isset($this->createdDirectoryIds[$directoryId]);
    }

    /**
     * Anchor the segments under the single seeded Root instead of creating new
     * top-level directories. When the tree is empty (fresh install / tests) the
     * first segment bootstraps the root.
     *
     * @param  array<int, string>  $segments
     */
    protected function resolveSegments(array $segments): int
    {
        $base = $this->baseDirectory();

        if ($base === null) {
            $rootName = array_shift($segments);

            return $this->resolveChildren($this->bootstrapRoot($rootName), $segments);
        }

        // A leading segment that already names the seeded root is the base itself,
        // not a child to create beneath it.
        if ($segments !== [] && $segments[0] === $base->name) {
            array_shift($segments);
        }

        return $this->resolveChildren($base->id, $segments);
    }

    protected function baseDirectory(): ?Directory
    {
        if (! $this->baseResolved) {
            $this->baseDirectory = Directory::whereNull('parent_id')->orderBy('id')->first();
            $this->baseResolved = true;
        }

        return $this->baseDirectory;
    }

    /**
     * Create the top-level root when none exists yet (fresh install / tests).
     */
    protected function bootstrapRoot(string $name): int
    {
        $cacheKey = 'root/'.$name;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $root = Directory::create(['name' => $name, 'parent_id' => null]);

        $this->directoryRepository->createDirectoryWithStorage($root->generatePath());

        $this->createdDirectoryIds[$root->id] = true;
        $this->baseDirectory = $root;
        $this->baseResolved = true;

        return $this->cache[$cacheKey] = $root->id;
    }

    public function destinationPath(string $damPath, ?string $fileName): string
    {
        $prefix = Directory::ASSETS_DIRECTORY.'/';
        $relative = $this->stripPrefix($damPath);

        $parts = $this->splitAndValidate($relative);

        $originalFile = array_pop($parts);

        $leaf = trim((string) $fileName) !== '' ? trim((string) $fileName) : $originalFile;

        if ($leaf === null || $leaf === '') {
            throw new InvalidPathException('Asset path has no file name: '.$damPath);
        }

        return $prefix.implode('/', [...$parts, $leaf]);
    }

    /**
     * @return array<int, string>
     */
    protected function directorySegments(string $damPath): array
    {
        $parts = $this->splitAndValidate($this->stripPrefix($damPath));

        array_pop($parts);

        return $parts;
    }

    protected function stripPrefix(string $damPath): string
    {
        $prefix = Directory::ASSETS_DIRECTORY.'/';
        $trimmed = ltrim(trim($damPath));

        return str_starts_with($trimmed, $prefix) ? substr($trimmed, strlen($prefix)) : $trimmed;
    }

    /**
     * @return array<int, string>
     */
    protected function splitAndValidate(string $relative): array
    {
        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', $relative)), fn ($p): bool => $p !== ''));

        foreach ($parts as $segment) {
            if ($segment === '.' || $segment === '..' || mb_strlen($segment) > 255 || preg_match(self::FORBIDDEN_SEGMENT, $segment)) {
                throw new InvalidPathException('Asset path contains an illegal segment: '.$segment);
            }
        }

        return $parts;
    }

    /**
     * @param  array<int, string>  $segments
     */
    protected function resolveChildren(int $parentId, array $segments): int
    {
        foreach ($segments as $segment) {
            $cacheKey = $parentId.'/'.$segment;

            if (! isset($this->cache[$cacheKey])) {
                $existing = Directory::where('parent_id', $parentId)->where('name', $segment)->first();

                $this->cache[$cacheKey] = $existing
                    ? $existing->id
                    : $this->createChild($parentId, $segment);
            }

            $parentId = $this->cache[$cacheKey];
        }

        return $parentId;
    }

    /**
     * Create a child directory, enforcing the same write permission the admin UI
     * requires (write access to the parent), and through DirectoryRepository so
     * the storage folder and filesystem-writability check happen.
     */
    protected function createChild(int $parentId, string $segment): int
    {
        if (! $this->permissionService->bypass() && ! $this->permissionService->canAccess($parentId)) {
            throw new DirectoryAccessDeniedException('No permission to create a directory under id '.$parentId);
        }

        $directory = $this->directoryRepository->create([
            'name'      => $segment,
            'parent_id' => $parentId,
        ]);

        $this->createdDirectoryIds[$directory->id] = true;

        return $directory->id;
    }
}
