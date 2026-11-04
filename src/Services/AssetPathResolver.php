<?php

namespace Webkul\DAM\Services;

use Webkul\DAM\Exceptions\InvalidPathException;
use Webkul\DAM\Models\Directory;

class AssetPathResolver
{
    protected const FORBIDDEN_SEGMENT = '/[\\\\\/\:\*\?\"\<\>\|]/u';

    protected array $cache = [];

    public function resolveDirectoryForPath(string $damPath): int
    {
        $segments = $this->directorySegments($damPath);

        if ($segments === []) {
            throw new InvalidPathException('Asset path is missing the root anchor: '.$damPath);
        }

        $rootName = array_shift($segments);

        $rootId = $this->resolveRoot($rootName);

        return $this->resolveChildren($rootId, $segments);
    }

    public function destinationPath(string $damPath, ?string $fileName): string
    {
        $prefix = Directory::ASSETS_DIRECTORY.'/';
        $trimmed = ltrim(trim($damPath));
        $relative = str_starts_with($trimmed, $prefix) ? substr($trimmed, strlen($prefix)) : $trimmed;

        $parts = $this->splitAndValidate($relative);

        $originalFile = array_pop($parts);

        $leaf = trim((string) $fileName) !== '' ? trim((string) $fileName) : $originalFile;

        if ($leaf === null || $leaf === '') {
            throw new InvalidPathException('Asset path has no file name: '.$damPath);
        }

        return $prefix.implode('/', [...$parts, $leaf]);
    }

    /**
     * The validated directory segments (root first, file removed) of `$damPath`.
     *
     * @return array<int, string>
     */
    protected function directorySegments(string $damPath): array
    {
        $prefix = Directory::ASSETS_DIRECTORY.'/';
        $trimmed = ltrim(trim($damPath));
        $relative = str_starts_with($trimmed, $prefix) ? substr($trimmed, strlen($prefix)) : $trimmed;

        $parts = $this->splitAndValidate($relative);

        array_pop($parts); // remove the file segment

        return $parts;
    }

    /**
     * @return array<int, string>
     */
    protected function splitAndValidate(string $relative): array
    {
        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', $relative)), fn ($p) => $p !== ''));

        foreach ($parts as $segment) {
            if ($segment === '.' || $segment === '..' || mb_strlen($segment) > 255 || preg_match(self::FORBIDDEN_SEGMENT, $segment)) {
                throw new InvalidPathException('Asset path contains an illegal segment: '.$segment);
            }
        }

        return $parts;
    }

    protected function resolveRoot(string $name): int
    {
        $cacheKey = 'root/'.$name;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $existing = Directory::whereNull('parent_id')->where('name', $name)->first();

        $id = $existing
            ? $existing->id
            : Directory::create(['name' => $name, 'parent_id' => null])->id;

        return $this->cache[$cacheKey] = $id;
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
                    : Directory::create(['name' => $segment, 'parent_id' => $parentId])->id;
            }

            $parentId = $this->cache[$cacheKey];
        }

        return $parentId;
    }
}
