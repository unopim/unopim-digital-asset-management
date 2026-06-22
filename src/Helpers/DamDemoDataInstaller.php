<?php

declare(strict_types=1);

namespace Webkul\DAM\Helpers;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Webkul\DAM\Database\Seeders\DamDemoDataSeeder;
use Webkul\DAM\Models\Directory;

class DamDemoDataInstaller
{
    /**
     * Seed DAM demo data.
     *
     * Idempotent: skips when data already present unless $force is true.
     *
     * @return array{success: bool, skipped?: bool, error?: string}
     */
    public function seed(?Closure $reporter = null, bool $force = false): array
    {
        $report = $reporter ?? static fn (string $message) => null;

        if (! $force && $this->isAlreadySeeded()) {
            $report('DAM demo data already seeded. Use --force to re-seed.');

            return ['success' => true, 'skipped' => true];
        }

        try {
            if ($force) {
                $report('Clearing existing DAM demo data...');
                $this->clearDemoData();
            }

            $report('Seeding DAM demo directories and assets...');
            app(DamDemoDataSeeder::class)->run();

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isAlreadySeeded(): bool
    {
        try {
            $root = DB::table('dam_directories')->whereNull('parent_id')->first();

            if (! $root) {
                return false;
            }

            return DB::table('dam_directories')
                ->where('parent_id', $root->id)
                ->where('name', 'Accessories')
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    protected function clearDemoData(): void
    {
        $disk = Directory::getAssetDisk();
        $prefix = Directory::ASSETS_DIRECTORY.'/Root/';

        $assetPaths = DB::table('dam_assets')
            ->where('path', 'like', $prefix.'%')
            ->pluck('path');

        foreach ($assetPaths as $path) {
            Storage::disk($disk)->delete($path);
        }

        DB::table('dam_assets')->where('path', 'like', $prefix.'%')->delete();

        $root = DB::table('dam_directories')->whereNull('parent_id')->first();

        if ($root) {
            $childIds = DB::table('dam_directories')
                ->where('parent_id', $root->id)
                ->pluck('id');

            foreach ($childIds as $childId) {
                $this->deleteDirectoryRecursive((int) $childId);
            }
        }

        DB::table('dam_tags')
            ->whereIn('name', ['Accessories', 'Audio and Video', 'Clothes', 'Documents'])
            ->delete();

        Directory::fixTree();
    }

    protected function deleteDirectoryRecursive(int $id): void
    {
        $childIds = DB::table('dam_directories')->where('parent_id', $id)->pluck('id');

        foreach ($childIds as $childId) {
            $this->deleteDirectoryRecursive((int) $childId);
        }

        DB::table('dam_directories')->where('id', $id)->delete();
    }
}
