<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Database\Seeders\DamDemoDataSeeder;
use Webkul\DAM\Database\Seeders\DirectoryTableSeeder;
use Webkul\DAM\Helpers\DamDemoDataInstaller;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    app(DirectoryTableSeeder::class)->run();
});

it('seeds 15 directories under root', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(
        DB::table('dam_directories')->whereNotNull('parent_id')->count()
    )->toBe(15);
});

it('seeds 85 assets', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(DB::table('dam_assets')->count())->toBe(85);
});

it('seeds 4 tags', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(DB::table('dam_tags')->count())->toBe(4);
});

it('links every asset to exactly one directory', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(DB::table('dam_asset_directory')->count())->toBe(85);
});

it('copies asset files to the storage disk', function () {
    app(DamDemoDataSeeder::class)->run();

    $files = Storage::disk(Directory::getAssetDisk())->allFiles('assets/Root');
    expect(count($files))->toBe(85);
});

it('creates correct file_type for image assets', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(
        DB::table('dam_assets')->where('extension', 'jpg')->value('file_type')
    )->toBe('image');
});

it('creates correct file_type for document assets', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(
        DB::table('dam_assets')->where('extension', 'pdf')->value('file_type')
    )->toBe('document');
});

it('assigns at least one tag-to-asset pivot row', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(DB::table('dam_asset_tag')->count())->toBeGreaterThan(0);
});

it('is idempotent — running twice does not double-seed assets', function () {
    app(DamDemoDataSeeder::class)->run();
    app(DamDemoDataSeeder::class)->run();

    expect(DB::table('dam_assets')->count())->toBe(85);
});

it('isAlreadySeeded returns false on fresh database', function () {
    expect(app(DamDemoDataInstaller::class)->isAlreadySeeded())->toBeFalse();
});

it('isAlreadySeeded returns true after seeding', function () {
    app(DamDemoDataSeeder::class)->run();

    expect(app(DamDemoDataInstaller::class)->isAlreadySeeded())->toBeTrue();
});

it('dam:demo-data command seeds successfully on fresh install', function () {
    $this->artisan('dam:demo-data')
        ->assertExitCode(0)
        ->expectsOutputToContain('seeded successfully');

    expect(DB::table('dam_assets')->count())->toBe(85);
});

it('dam:demo-data command reports already seeded when run twice', function () {
    app(DamDemoDataSeeder::class)->run();

    $this->artisan('dam:demo-data')
        ->assertExitCode(0)
        ->expectsOutputToContain('already present');
});

it('dam:demo-data --force clears and re-seeds to same count', function () {
    app(DamDemoDataSeeder::class)->run();

    $this->artisan('dam:demo-data', ['--force' => true])
        ->expectsConfirmation('This will permanently delete all existing DAM assets. Continue?', 'yes')
        ->assertExitCode(0)
        ->expectsOutputToContain('seeded successfully');

    expect(DB::table('dam_assets')->count())->toBe(85);
    expect(DB::table('dam_directories')->whereNotNull('parent_id')->count())->toBe(15);
});
