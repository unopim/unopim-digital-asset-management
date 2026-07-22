<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Helpers\DamUpdater;

uses(DatabaseTransactions::class);

it('passes when no tracked rows are lost', function () {
    $updater = app(DamUpdater::class);
    $before = $updater->countRows();

    DB::table('dam_tags')->insert(['name' => 'kept-tag', 'created_at' => now(), 'updated_at' => now()]);

    $result = $updater->verify($before);

    expect($result['ok'])->toBeTrue()
        ->and($result['dropped'])->toBe([]);
});

it('fails when a tracked table loses rows', function () {
    $updater = app(DamUpdater::class);
    DB::table('dam_tags')->insert(['name' => 'will-drop', 'created_at' => now(), 'updated_at' => now()]);
    $before = $updater->countRows();

    DB::table('dam_tags')->where('name', 'will-drop')->delete();

    $result = $updater->verify($before);

    expect($result['ok'])->toBeFalse()
        ->and($result['dropped'])->toContain('dam_tags');
});
