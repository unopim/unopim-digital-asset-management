<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;

/**
 * Resolves DAM asset paths to ids for an import batch.
 *
 * Importers previously queried once per asset value per row; a batch referencing the
 * same asset a thousand times issued a thousand queries. Paths are collected up front
 * and resolved in chunks instead, and lookups afterwards are in-memory.
 */
class AssetPathStorage
{
    /**
     * @var array<string, int>
     */
    protected array $items = [];

    /**
     * Resolve any of the given paths not already known, in chunks so a large batch
     * cannot overflow the driver's bind-parameter limit.
     *
     * @param  list<string>  $paths
     */
    public function load(array $paths): void
    {
        $pending = array_values(array_unique(array_filter(
            $paths,
            fn (string $path): bool => $path !== '' && ! isset($this->items[$path])
        )));

        if ($pending === []) {
            return;
        }

        foreach (array_chunk($pending, 1000) as $chunk) {
            $assets = DB::table((new Asset)->getTable())
                ->select(['id', 'path'])
                ->whereIn('path', $chunk)
                ->get();

            foreach ($assets as $asset) {
                $this->items[$asset->path] = (int) $asset->id;
            }
        }
    }

    public function has(string $path): bool
    {
        return isset($this->items[$path]);
    }

    public function get(string $path): ?int
    {
        return $this->items[$path] ?? null;
    }

    public function set(string $path, int $id): self
    {
        $this->items[$path] = $id;

        return $this;
    }

    public function flush(): self
    {
        $this->items = [];

        return $this;
    }
}
