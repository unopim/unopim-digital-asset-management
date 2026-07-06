<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

it('finalizes and verifies without losing rows', function () {
    $before = DB::table('dam_tags')->count();

    $this->artisan('dam:update --skip-backup')
        ->expectsConfirmation('Continue without a backup?', 'yes')
        ->expectsOutputToContain('Verify')
        ->assertSuccessful();

    expect(DB::table('dam_tags')->count())->toBeGreaterThanOrEqual($before);
});
