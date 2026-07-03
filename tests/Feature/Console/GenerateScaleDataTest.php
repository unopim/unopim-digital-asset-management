<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();

    $sourceDir = storage_path('app/private/assets/Root');

    if (! is_dir($sourceDir)) {
        mkdir($sourceDir, 0755, true);
    }

    $dummyFile = $sourceDir.'/sample.jpg';

    if (! file_exists($dummyFile)) {
        file_put_contents($dummyFile, str_repeat("\0", 1024));
    }
});

it('creates the requested number of directories', function () {
    $existingDirs = Directory::count();

    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 100,
        '--directories' => 20,
        '--chunk'       => 50,
    ])->assertSuccessful();

    expect(Directory::count())->toBeGreaterThanOrEqual($existingDirs + 20);
});

it('creates the requested number of assets', function () {
    $existingAssets = DB::table('dam_assets')->count();

    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 80,
        '--directories' => 15,
        '--chunk'       => 40,
    ])->assertSuccessful();

    expect(DB::table('dam_assets')->count())->toBe($existingAssets + 80);
});

it('creates pivot records linking each asset to its directory', function () {
    $existingPivots = DB::table('dam_asset_directory')->count();

    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 60,
        '--directories' => 10,
        '--chunk'       => 30,
    ])->assertSuccessful();

    expect(DB::table('dam_asset_directory')->count())->toBe($existingPivots + 60);
});

it('sets valid nested-set columns on all new directories', function () {
    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 10,
        '--directories' => 15,
        '--chunk'       => 15,
    ])->assertSuccessful();

    expect(Directory::whereNull('_lft')->count())->toBe(0);
    expect(Directory::whereNull('_rgt')->count())->toBe(0);
});

it('hard-links files so physical paths exist on disk', function () {
    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 20,
        '--directories' => 5,
        '--chunk'       => 20,
    ])->assertSuccessful();

    $storageRoot = storage_path('app/private');

    DB::table('dam_assets')
        ->orderByDesc('id')
        ->limit(20)
        ->get(['path'])
        ->each(function ($asset) use ($storageRoot) {
            expect(file_exists($storageRoot.'/'.$asset->path))->toBeTrue(
                "Expected physical file at: {$storageRoot}/{$asset->path}"
            );
        });
});

it('dry-run makes no DB or filesystem changes', function () {
    $dirsBefore = Directory::count();
    $assetsBefore = DB::table('dam_assets')->count();
    $pivotsBefore = DB::table('dam_asset_directory')->count();

    $this->artisan('dam:generate-scale-data', [
        '--assets'      => 100,
        '--directories' => 20,
        '--dry-run'     => true,
    ])->assertSuccessful();

    expect(Directory::count())->toBe($dirsBefore);
    expect(DB::table('dam_assets')->count())->toBe($assetsBefore);
    expect(DB::table('dam_asset_directory')->count())->toBe($pivotsBefore);
});
