<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;

class AssetPathStorage
{
    /**
     * @var array<string, int>
     */
    protected array $items = [];

    /**
     * @var array<string, true>
     */
    protected array $missing = [];

    /**
     * @param  list<string>  $paths
     */
    public function load(array $paths): void
    {
        $pending = array_values(array_unique(array_filter(
            $paths,
            fn (string $path): bool => $path !== '' && ! isset($this->items[$path]) && ! isset($this->missing[$path])
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

            foreach ($chunk as $path) {
                if (! isset($this->items[$path])) {
                    $this->missing[$path] = true;
                }
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

        unset($this->missing[$path]);

        return $this;
    }

    public function flush(): self
    {
        $this->items = [];

        $this->missing = [];

        return $this;
    }
}
