<?php

declare(strict_types=1);

namespace Webkul\DAM\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Directory;

class DamDemoDataSeeder extends Seeder
{
    private array $directoryTree = [
        'Audio' => [
            'Podcasts'    => [],
            'Sound Logos' => [],
        ],
        'Brand' => [
            'Guidelines' => [],
            'Logos'      => [],
        ],
        'Documents' => [
            'Contracts'   => [],
            'Datasheets'  => [],
            'Price Lists' => [],
        ],
        'Marketing' => [
            'Campaign Videos' => [],
            'Social Clips'    => [],
        ],
        'Product Photography' => [
            'Apparel'   => [],
            'Audio'     => [],
            'Furniture' => [],
            'Outdoor'   => [],
        ],
    ];

    public function run(): void
    {
        $root = Directory::whereNull('parent_id')->where('name', 'Root')->first();

        if (! $root) {
            $this->command?->error('Root directory not found. Run DirectoryTableSeeder first.');

            return;
        }

        if ($root->children()->where('name', 'Brand')->exists()) {
            return;
        }

        $sourceRoot = __DIR__.'/../Data/demo-assets/Root';

        if (! File::isDirectory($sourceRoot)) {
            $this->command?->error('Demo assets not found at: '.$sourceRoot);

            return;
        }

        $now = Carbon::now();

        $directoryMap = array_merge(
            ['' => $root],
            $this->createDirectoryTree($this->directoryTree, $root)
        );

        Directory::fixTree();

        $this->seedAssets($sourceRoot, $directoryMap, $now);
        $this->seedTags($directoryMap, $now);

        DatabaseSequenceHelper::fixSequences(['dam_directories', 'dam_assets', 'dam_tags']);
    }

    protected function createDirectoryTree(array $tree, Directory $parent, string $prefix = ''): array
    {
        $map = [];

        foreach ($tree as $name => $children) {
            $dir = Directory::create(['name' => $name, 'parent_id' => $parent->id]);
            $key = $prefix !== '' ? $prefix.'/'.$name : $name;
            $map[$key] = $dir;

            if (! empty($children)) {
                $map = array_merge($map, $this->createDirectoryTree($children, $dir, $key));
            }
        }

        return $map;
    }

    protected function seedAssets(string $sourceRoot, array $directoryMap, Carbon $now): void
    {
        $disk = Directory::getAssetDisk();
        $assetRows = [];
        $assetPathToDirectoryId = [];

        foreach (File::allFiles($sourceRoot) as $file) {
            $relDir = str_replace('\\', '/', $file->getRelativePath());
            $directory = $directoryMap[$relDir] ?? null;

            if (! $directory) {
                continue;
            }

            $fileName = $file->getFilename();
            $storagePath = Directory::ASSETS_DIRECTORY.'/Root/'.($relDir !== '' ? $relDir.'/' : '').$fileName;
            $mimeType = mime_content_type($file->getRealPath()) ?: 'application/octet-stream';
            $extension = strtolower($file->getExtension());

            Storage::disk($disk)->put($storagePath, File::get($file->getRealPath()));

            $assetRows[] = [
                'file_name'  => $fileName,
                'file_type'  => $this->resolveFileType($mimeType),
                'file_size'  => $file->getSize(),
                'mime_type'  => $mimeType,
                'extension'  => $extension,
                'path'       => $storagePath,
                'meta_data'  => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $assetPathToDirectoryId[$storagePath] = $directory->id;
        }

        if (empty($assetRows)) {
            return;
        }

        DB::table('dam_assets')->insert($assetRows);

        $insertedAssets = DB::table('dam_assets')
            ->whereIn('path', array_column($assetRows, 'path'))
            ->get(['id', 'path']);

        $pivotRows = [];

        foreach ($insertedAssets as $asset) {
            $dirId = $assetPathToDirectoryId[$asset->path] ?? null;

            if (! $dirId) {
                continue;
            }

            $pivotRows[] = [
                'asset_id'     => $asset->id,
                'directory_id' => $dirId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if (! empty($pivotRows)) {
            DB::table('dam_asset_directory')->insert($pivotRows);
        }

        $this->finaliseAssets($insertedAssets->pluck('id')->all());
    }

    /**
     * Run each seeded asset through the same job a real upload uses, so demo rows
     * carry metadata, audio cover art and video/PDF thumbnails rather than a null
     * `meta_data`.
     *
     * `ProcessAssetUpload` dispatches its thumbnail jobs onto the configured queue.
     * Forcing `sync` for the duration of the loop makes those nested dispatches run
     * inline, so `dam:demo-data` returns only once the library is complete instead of
     * leaving work on a queue that may have no worker.
     *
     * A host without ffmpeg, ghostscript or exiftool falls back to `meta_data => null`
     * for the affected asset; that must not fail the seed.
     */
    protected function finaliseAssets(array $assetIds): void
    {
        $queueConnection = config('queue.default');

        config(['queue.default' => 'sync']);

        try {
            foreach ($assetIds as $assetId) {
                try {
                    ProcessAssetUpload::dispatchSync((int) $assetId);
                } catch (\Throwable $e) {
                    Log::warning('DAM demo asset finalisation failed.', [
                        'asset'   => $assetId,
                        'message' => $e->getMessage(),
                    ]);

                    $this->command?->warn("Could not finalise asset {$assetId}: {$e->getMessage()}");
                }
            }
        } finally {
            config(['queue.default' => $queueConnection]);
        }
    }

    protected function seedTags(array $directoryMap, Carbon $now): void
    {
        foreach (array_keys($this->directoryTree) as $tagName) {
            if (DB::table('dam_tags')->where('name', $tagName)->exists()) {
                continue;
            }

            $tagId = DB::table('dam_tags')->insertGetId([
                'name'       => $tagName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $dirIds = collect($directoryMap)
                ->filter(fn ($dir, $key) => $key === $tagName || str_starts_with($key, $tagName.'/'))
                ->map(fn ($dir) => $dir->id)
                ->values()
                ->toArray();

            if (empty($dirIds)) {
                continue;
            }

            $assetIds = DB::table('dam_asset_directory')
                ->whereIn('directory_id', $dirIds)
                ->pluck('asset_id')
                ->unique()
                ->values()
                ->toArray();

            $tagPivotRows = array_map(fn ($assetId) => [
                'asset_id'   => $assetId,
                'tag_id'     => $tagId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $assetIds);

            if (! empty($tagPivotRows)) {
                DB::table('dam_asset_tag')->insertOrIgnore($tagPivotRows);
            }
        }
    }

    protected function resolveFileType(string $mimeType): string
    {
        if (str_contains($mimeType, 'image')) {
            return 'image';
        }

        if (str_contains($mimeType, 'video')) {
            return 'video';
        }

        if (str_contains($mimeType, 'audio')) {
            return 'audio';
        }

        return 'document';
    }
}
