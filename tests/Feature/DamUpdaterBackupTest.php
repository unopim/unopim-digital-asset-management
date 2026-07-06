<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\DamUpdater;
use Webkul\DAM\Models\Directory;

beforeEach(fn () => Storage::fake(Directory::getAssetDisk()));

it('builds a mysqldump argv containing every dam table', function () {
    config(['database.default' => 'mysql']);
    $cmd = app(DamUpdater::class)->buildDumpCommand('/tmp/out.sql');

    expect($cmd[0])->toBe('mysqldump')
        ->and($cmd)->toContain('dam_assets')
        ->and($cmd)->toContain('wk_assets_action_request');
});

it('creates a unique backup dir', function () {
    $updater = app(DamUpdater::class);
    $a = $updater->backupDir('2026-07-03_10-00-00');
    $b = $updater->backupDir('2026-07-03_10-00-00');

    expect(is_dir($a))->toBeTrue()
        ->and($b)->not->toBe($a); // second call must not collide

    @rmdir($a);
    @rmdir($b);
});
