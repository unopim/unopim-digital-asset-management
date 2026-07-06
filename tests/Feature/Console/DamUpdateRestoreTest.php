<?php

use Webkul\DAM\Helpers\DamUpdater;

it('lists available backups newest first', function () {
    @mkdir(storage_path('dam-backups/2026-01-01_00-00-00'), 0755, true);
    @mkdir(storage_path('dam-backups/2026-02-01_00-00-00'), 0755, true);

    $list = app(DamUpdater::class)->listBackups();

    expect($list[0])->toBe('2026-02-01_00-00-00');

    @rmdir(storage_path('dam-backups/2026-01-01_00-00-00'));
    @rmdir(storage_path('dam-backups/2026-02-01_00-00-00'));
});

it('errors clearly when the timestamp is unknown', function () {
    $this->artisan('dam:update:restore does-not-exist')
        ->expectsOutputToContain('No backup')
        ->assertFailed();
});
