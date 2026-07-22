<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\DamUpdater;
use Webkul\DAM\Models\Directory;

uses(DatabaseTransactions::class);

beforeEach(fn () => Storage::fake(Directory::getAssetDisk()));

it('dry-run reports the plan and mutates nothing', function () {
    $before = DB::table('dam_tags')->count();

    $this->artisan('dam:update --dry-run')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(DB::table('dam_tags')->count())->toBe($before);
});

it('orchestrates migrate, publish and verify in order', function () {
    $counts = ['dam_assets' => 3, 'dam_directories' => 2, 'dam_tags' => 1];

    $updater = Mockery::mock(DamUpdater::class);
    $updater->shouldReceive('countRows')->once()->andReturn($counts);
    $updater->shouldNotReceive('backup');
    $updater->shouldReceive('runMigrations')->once()->ordered();
    $updater->shouldReceive('publish')->once()->ordered();
    $updater->shouldReceive('clearCaches')->once()->ordered();
    $updater->shouldReceive('verify')->once()->ordered()->with($counts)->andReturn([
        'ok'      => true,
        'before'  => $counts,
        'after'   => $counts,
        'dropped' => [],
    ]);

    $this->app->instance(DamUpdater::class, $updater);

    $this->artisan('dam:update --skip-backup')
        ->expectsConfirmation('Continue without a backup?', 'yes')
        ->expectsOutputToContain('Verify')
        ->assertSuccessful();
});

it('fails when verification reports dropped rows', function () {
    $counts = ['dam_assets' => 3];

    $updater = Mockery::mock(DamUpdater::class);
    $updater->shouldReceive('countRows')->once()->andReturn($counts);
    $updater->shouldReceive('runMigrations')->once();
    $updater->shouldReceive('publish')->once();
    $updater->shouldReceive('clearCaches')->once();
    $updater->shouldReceive('verify')->once()->andReturn([
        'ok'      => false,
        'before'  => $counts,
        'after'   => ['dam_assets' => 1],
        'dropped' => ['dam_assets'],
    ]);

    $this->app->instance(DamUpdater::class, $updater);

    $this->artisan('dam:update --skip-backup')
        ->expectsConfirmation('Continue without a backup?', 'yes')
        ->expectsOutputToContain('dam_assets')
        ->assertFailed();
});

it('aborts without running anything when the backup confirmation is declined', function () {
    $updater = Mockery::mock(DamUpdater::class);
    $updater->shouldReceive('countRows')->once()->andReturn(['dam_assets' => 3]);
    $updater->shouldNotReceive('runMigrations');
    $updater->shouldNotReceive('publish');
    $updater->shouldNotReceive('verify');

    $this->app->instance(DamUpdater::class, $updater);

    $this->artisan('dam:update --skip-backup')
        ->expectsConfirmation('Continue without a backup?', 'no')
        ->assertSuccessful();
});
