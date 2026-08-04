<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Directory;

class GenerateScaleData extends Command
{
    protected $signature = 'dam:generate-scale-data
        {--assets=1000000 : Number of assets to generate}
        {--directories=50000 : Number of directories to create (excluding existing)}
        {--chunk=10000 : DB insert batch size}
        {--dry-run : Exit without performing any writes}
        {--repair-pivots : Insert missing pivot rows for assets that have no directory link}';

    protected $description = 'Seed large-scale DAM data for performance and UI validation testing';

    private string $disk = 'private';

    private string $storageRoot;

    private string $sourceDir;

    private array $sourceMeta = [];

    public function handle(): int
    {
        $targetAssets = (int) $this->option('assets');
        $targetDirs = (int) $this->option('directories');
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');
        $repairPivots = (bool) $this->option('repair-pivots');

        $this->storageRoot = storage_path('app/private');
        $this->sourceDir = $this->storageRoot.'/assets/Root';

        if ($repairPivots) {
            return $this->repairPivots($chunk);
        }

        $this->info('DAM Scale Data Generator');
        $this->info("  Target assets      : {$targetAssets}");
        $this->info("  Target directories : {$targetDirs}");
        $this->info("  Chunk size         : {$chunk}");

        if ($dryRun) {
            $this->warn('[DRY RUN] No writes will occur.');

            return self::SUCCESS;
        }

        $this->loadSourceFiles();

        $this->info("\n[Phase 1] Building directory tree...");
        $nodes = $this->buildDirectoryTree($targetDirs);
        $this->info('  Built '.count($nodes).' directory nodes in memory.');

        if ($nodes !== []) {
            $this->info("\n[Phase 2] Bulk-inserting directories...");
            $this->insertDirectories($nodes, $chunk);

            $this->info("\n[Phase 3] Creating storage folders...");
            $this->createStorageDirs($nodes);
        } else {
            $this->info("\n[Phase 2+3] --directories=0 — skipping directory creation, using existing dirs.");
        }

        $this->info("\n[Phase 4] Hard-linking files and building asset records...");
        $assetRows = $this->linkFilesAndBuildAssets($nodes, $targetAssets);
        $this->info('  Linked '.count($assetRows).' files.');

        $this->info("\n[Phase 5] Bulk-inserting asset records...");
        $assetIds = $this->insertAssets($assetRows, $chunk);

        $this->info("\n[Phase 6] Bulk-inserting pivot records...");
        $this->insertPivots($assetRows, $assetIds, $chunk);

        $this->info("\n✓ Done.");
        $this->info('  DB assets      : '.DB::table('dam_assets')->count());
        $this->info('  DB directories : '.DB::table('dam_directories')->count());

        return self::SUCCESS;
    }

    private function repairPivots(int $chunk): int
    {
        $this->info('Building directory storage-path map...');
        $pathMap = $this->buildStoragePathMap();
        $pathToId = array_flip($pathMap);

        $this->info('Finding assets with no pivot row...');
        $unpivoted = DB::table('dam_assets')
            ->leftJoin('dam_asset_directory', 'dam_assets.id', '=', 'dam_asset_directory.asset_id')
            ->whereNull('dam_asset_directory.asset_id')
            ->select('dam_assets.id', 'dam_assets.path')
            ->get();

        $total = $unpivoted->count();
        $this->info("  Found {$total} unpivoted assets.");

        if ($total === 0) {
            $this->info('Nothing to repair.');

            return self::SUCCESS;
        }

        $now = now()->toDateTimeString();
        $pivots = [];
        $missed = 0;

        foreach ($unpivoted as $row) {
            $dirPath = dirname($row->path);
            $dirId = $pathToId[$dirPath] ?? null;

            if ($dirId === null) {
                $missed++;

                continue;
            }

            $pivots[] = [
                'asset_id'     => $row->id,
                'directory_id' => (int) $dirId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if ($missed > 0) {
            $this->warn("  Could not resolve directory for {$missed} assets — skipping those.");
        }

        $this->info('Inserting '.count($pivots).' pivot rows...');
        $bar = $this->output->createProgressBar(count($pivots));
        $bar->start();

        $safeChunk = min($chunk, (int) floor(65535 / 4));

        foreach (array_chunk($pivots, $safeChunk) as $batch) {
            DB::table('dam_asset_directory')->insertOrIgnore($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        $this->info('✓ Repair done. Total pivots: '.DB::table('dam_asset_directory')->count());

        return self::SUCCESS;
    }

    private function buildStoragePathMap(): array
    {
        $all = DB::table('dam_directories')
            ->select(['id', 'name', 'parent_id'])
            ->get()
            ->keyBy('id')
            ->all();

        $resolved = [];

        $resolve = function (int $id) use (&$resolve, $all, &$resolved): string {
            if (isset($resolved[$id])) {
                return $resolved[$id];
            }

            $row = $all[$id] ?? null;

            if ($row === null) {
                return 'assets/Root';
            }

            $path = $row->parent_id === null
                ? 'assets/'.$row->name
                : $resolve((int) $row->parent_id).'/'.$row->name;

            $resolved[$id] = $path;

            return $path;
        };

        foreach (array_keys($all) as $id) {
            $resolve((int) $id);
        }

        return $resolved;
    }

    private function loadSourceFiles(): void
    {
        $files = glob($this->sourceDir.'/*');

        if ($files === false) {
            throw new \RuntimeException("Cannot read source directory: {$this->sourceDir}");
        }

        if ($files === []) {
            throw new \RuntimeException("No source files found in {$this->sourceDir}");
        }

        foreach ($files as $fullPath) {
            if (! is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            $mimeType = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png'         => 'image/png',
                'webp'        => 'image/webp',
                'gif'         => 'image/gif',
                'mp3'         => 'audio/mpeg',
                'mp4'         => 'video/mp4',
                'mkv'         => 'video/x-matroska',
                'pdf'         => 'application/pdf',
                default       => 'application/octet-stream',
            };

            $fileType = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'audio/') => 'audio',
                str_starts_with($mimeType, 'video/') => 'video',
                default                              => 'document',
            };

            $this->sourceMeta[] = [
                'full_path' => $fullPath,
                'file_name' => basename($fullPath),
                'extension' => $ext,
                'mime_type' => $mimeType,
                'file_type' => $fileType,
                'file_size' => (int) (filesize($fullPath) ?: 0),
            ];
        }

        $this->info('  Loaded '.count($this->sourceMeta).' source files.');
    }

    private function buildDirectoryTree(int $target): array
    {
        $now = now()->toDateTimeString();
        $nextId = (int) DB::table('dam_directories')->max('id') + 1;
        $nodes = [];
        $total = 0;

        $prefixes = [
            'Alpha', 'Beta', 'Campaign', 'Digital', 'Event', 'Fall', 'Global',
            'Heritage', 'Impact', 'Journey', 'Kilo', 'Legacy', 'Macro', 'Nordic',
            'Orbit', 'Prime', 'Quantum', 'Retail', 'Studio', 'Terra', 'Urban',
            'Vivid', 'Wave', 'Xenon', 'Yield', 'Zeta',
        ];

        $categories = [
            'Images', 'Videos', 'Documents', 'Audio', 'Archives',
            'Products', 'Marketing', 'Brand', 'Social', 'Print',
            'Web', 'Mobile', 'Desktop', 'Thumbnails', 'Originals',
            'Exports', 'Imports', 'Backups', 'Templates', 'Drafts',
        ];

        $seasons = ['Spring', 'Summer', 'Fall', 'Winter'];
        $years = ['2023', '2024', '2025', '2026'];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $types = ['Raw', 'Edited', 'Final', 'Draft', 'Approved', 'Rejected', 'Archived'];

        $makeNode = function (string $name, int $parentId, string $parentStoragePath) use (&$nextId, $now): array {
            return [
                'id'            => $nextId++,
                'name'          => $name,
                'parent_id'     => $parentId,
                '_lft'          => 0,
                '_rgt'          => 0,
                'created_at'    => $now,
                'updated_at'    => $now,
                '_storage_path' => $parentStoragePath.'/'.$name,
            ];
        };

        $rootDir = Directory::whereNull('parent_id')->orderBy('id')->first();
        $rootId = (int) ($rootDir?->id ?? 1);
        $rootStoragePath = 'assets/'.($rootDir?->name ?? 'Root');

        $l1 = [];
        for ($i = 0; $i < 200 && $total < $target; $i++) {
            $name = ($prefixes[$i % count($prefixes)]).'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $node = $makeNode($name, $rootId, $rootStoragePath);
            $nodes[] = $node;
            $l1[] = $node;
            $total++;
        }

        $l2 = [];
        foreach ($l1 as $parent) {
            for ($i = 0; $i < 10 && $total < $target; $i++) {
                $name = $categories[$i % count($categories)].'-'.($i + 1);
                $node = $makeNode($name, $parent['id'], $parent['_storage_path']);
                $nodes[] = $node;
                $l2[] = $node;
                $total++;
            }
        }

        $l3 = [];
        foreach ($l2 as $parent) {
            for ($i = 0; $i < 5 && $total < $target; $i++) {
                $name = $years[$i % count($years)].'-'.$seasons[$i % count($seasons)];
                $node = $makeNode($name, $parent['id'], $parent['_storage_path']);
                $nodes[] = $node;
                $l3[] = $node;
                $total++;
            }
        }

        $l4 = [];
        foreach ($l3 as $parent) {
            for ($i = 0; $i < 3 && $total < $target; $i++) {
                $name = $months[$i % count($months)];
                $node = $makeNode($name, $parent['id'], $parent['_storage_path']);
                $nodes[] = $node;
                $l4[] = $node;
                $total++;
            }
        }

        if ($total < $target) {
            shuffle($l4);
            $deepPool = array_slice($l4, 0, (int) (count($l4) * 0.2));

            foreach ($deepPool as $l4Node) {
                if ($total >= $target) {
                    break;
                }

                $current = $l4Node;
                for ($depth = 5; $depth <= 10 && $total < $target; $depth++) {
                    $name = $types[$depth % count($types)].'-D'.$depth;
                    $node = $makeNode($name, $current['id'], $current['_storage_path']);
                    $nodes[] = $node;
                    $current = $node;
                    $total++;
                }
            }
        }

        return $nodes;
    }

    private function insertDirectories(array $nodes, int $chunk): void
    {
        $dbRows = array_map(fn (array $n): array => [
            'id'         => $n['id'],
            'name'       => $n['name'],
            'parent_id'  => $n['parent_id'],
            '_lft'       => $n['_lft'],
            '_rgt'       => $n['_rgt'],
            'created_at' => $n['created_at'],
            'updated_at' => $n['updated_at'],
        ], $nodes);

        $bar = $this->output->createProgressBar(count($dbRows));
        $bar->start();

        $safeChunk = min($chunk, (int) floor(65535 / 7));

        foreach (array_chunk($dbRows, $safeChunk) as $batch) {
            DB::table('dam_directories')->insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        $this->info('  Running fixTree() to rebuild nested-set columns...');
        (new Directory)->newNestedSetQuery()->fixTree();
        $this->info('  fixTree() complete.');

        $this->resetSequence('dam_directories');
    }

    private function createStorageDirs(array $nodes): void
    {
        $bar = $this->output->createProgressBar(count($nodes));
        $bar->start();

        foreach ($nodes as $node) {
            $absPath = $this->storageRoot.'/'.$node['_storage_path'];

            if (! is_dir($absPath)) {
                mkdir($absPath, 0755, true);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function linkFilesAndBuildAssets(array $nodes, int $targetAssets): array
    {
        if ($nodes === []) {
            $this->info('  Loading existing directories from DB...');
            $pathMap = $this->buildStoragePathMap();
            $nodes = DB::table('dam_directories')
                ->whereNotNull('parent_id')
                ->select(['id', 'name', 'parent_id'])
                ->get()
                ->map(fn ($row) => [
                    'id'            => $row->id,
                    'name'          => $row->name,
                    'parent_id'     => $row->parent_id,
                    '_storage_path' => $pathMap[$row->id] ?? ('assets/Root/'.$row->name),
                ])
                ->all();
            $this->info('  Loaded '.count($nodes).' existing directories.');
        }

        $nodeCount = count($nodes);

        if ($nodeCount === 0) {
            $this->warn('No directory nodes — skipping asset linking.');

            return [];
        }

        $assetRows = [];
        $now = now()->toDateTimeString();
        $sourceCount = count($this->sourceMeta);

        $basePerNode = (int) floor($targetAssets / $nodeCount);
        $remainder = $targetAssets - ($basePerNode * $nodeCount);
        $nodeCounts = array_fill(0, $nodeCount, $basePerNode);

        for ($i = 0; $i < $remainder; $i++) {
            $nodeCounts[$i % $nodeCount]++;
        }

        shuffle($nodeCounts);

        $bar = $this->output->createProgressBar($targetAssets);
        $bar->start();

        $sourceIdx = 0;

        foreach ($nodes as $idx => $node) {
            $dirAbsPath = $this->storageRoot.'/'.$node['_storage_path'];
            $count = $nodeCounts[$idx];

            for ($i = 0; $i < $count; $i++) {
                $src = $this->sourceMeta[$sourceIdx % $sourceCount];
                $hex = bin2hex(random_bytes(4));
                $stem = pathinfo($src['file_name'], PATHINFO_FILENAME);
                $newName = $stem.'_'.$hex.'.'.$src['extension'];
                $destAbs = $dirAbsPath.'/'.$newName;
                $dbPath = $node['_storage_path'].'/'.$newName;

                if (! @link($src['full_path'], $destAbs)) {
                    if (! copy($src['full_path'], $destAbs)) {
                        throw new \RuntimeException("Failed to link or copy '{$src['full_path']}' to '{$destAbs}'");
                    }
                }

                $assetRows[] = [
                    'file_name'  => $newName,
                    'file_type'  => $src['file_type'],
                    'file_size'  => $src['file_size'],
                    'mime_type'  => $src['mime_type'],
                    'extension'  => $src['extension'],
                    'path'       => $dbPath,
                    'created_at' => $now,
                    'updated_at' => $now,
                    '_dir_id'    => $node['id'],
                ];

                $sourceIdx++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        return $assetRows;
    }

    private function insertAssets(array $assetRows, int $chunk): array
    {
        if (empty($assetRows)) {
            return [];
        }

        $dbRows = array_map(fn (array $r): array => [
            'file_name'  => $r['file_name'],
            'file_type'  => $r['file_type'],
            'file_size'  => $r['file_size'],
            'mime_type'  => $r['mime_type'],
            'extension'  => $r['extension'],
            'path'       => $r['path'],
            'created_at' => $r['created_at'],
            'updated_at' => $r['updated_at'],
        ], $assetRows);

        $bar = $this->output->createProgressBar(count($dbRows));
        $bar->start();

        $insertedBefore = (int) DB::table('dam_assets')->max('id');

        $safeChunk = min($chunk, (int) floor(65535 / 8));

        foreach (array_chunk($dbRows, $safeChunk) as $batch) {
            DB::table('dam_assets')->insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();

        $assetIds = DB::table('dam_assets')
            ->where('id', '>', $insertedBefore)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $assetIds;
    }

    private function insertPivots(array $assetRows, array $assetIds, int $chunk): void
    {
        if (empty($assetIds)) {
            return;
        }

        if (count($assetIds) > count($assetRows)) {
            $assetIds = array_slice($assetIds, count($assetIds) - count($assetRows));
        }

        if (count($assetIds) !== count($assetRows)) {
            throw new \RuntimeException(
                'insertPivots: assetIds count ('.count($assetIds).') does not match assetRows count ('.count($assetRows).')'
            );
        }

        $now = now()->toDateTimeString();
        $pivots = [];

        foreach ($assetIds as $i => $assetId) {
            $pivots[] = [
                'asset_id'     => $assetId,
                'directory_id' => $assetRows[$i]['_dir_id'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        $bar = $this->output->createProgressBar(count($pivots));
        $bar->start();

        $safeChunk = min($chunk, (int) floor(65535 / 4));

        foreach (array_chunk($pivots, $safeChunk) as $batch) {
            DB::table('dam_asset_directory')->insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();
    }

    private function resetSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $prefixed = DB::getTablePrefix().$table;
        $row = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS seq", [$prefixed]);

        if (! $row || ! $row->seq) {
            return;
        }

        $max = DB::selectOne("SELECT MAX(id) AS mx FROM \"{$prefixed}\"");

        if (! $max || ! $max->mx) {
            return;
        }

        DB::statement("SELECT setval('{$row->seq}', {$max->mx})");
        $this->info("  Sequence reset: {$prefixed} → {$max->mx}");
    }
}
