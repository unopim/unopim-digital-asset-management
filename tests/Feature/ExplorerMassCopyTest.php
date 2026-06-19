<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Webkul\DAM\Jobs\MassCopy;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('mass copies directories to target by dispatching jobs', function () {
    Bus::fake();

    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => null]);
    $dir1 = Directory::factory()->create(['name' => 'DirA', 'parent_id' => null]);
    $dir2 = Directory::factory()->create(['name' => 'DirB', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.explorer.mass_copy'), [
        'asset_ids'           => [],
        'directory_ids'       => [$dir1->id, $dir2->id],
        'target_directory_id' => $target->id,
    ]);

    $response->assertOk();
    Bus::assertDispatchedTimes(MassCopy::class, 1);
});

it('skips root directory (protected by isCopyable) in mass copy', function () {
    Bus::fake();

    $root = Directory::find(1) ?? Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.explorer.mass_copy'), [
        'asset_ids'           => [],
        'directory_ids'       => [$root->id],
        'target_directory_id' => $target->id,
    ]);

    $response->assertOk();
    Bus::assertNotDispatched(CopyDirectory::class);
});

it('returns 422 when target_directory_id is missing', function () {
    $response = $this->postJson(route('admin.dam.explorer.mass_copy'), [
        'asset_ids'     => [],
        'directory_ids' => [],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['target_directory_id']);
});

it('returns 422 when target_directory_id does not exist', function () {
    $response = $this->postJson(route('admin.dam.explorer.mass_copy'), [
        'asset_ids'           => [],
        'directory_ids'       => [],
        'target_directory_id' => 999999,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['target_directory_id']);
});
