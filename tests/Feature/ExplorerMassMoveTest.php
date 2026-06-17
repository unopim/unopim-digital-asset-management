<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Webkul\DAM\Jobs\MoveDirectoryStructure;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('mass moves directories to target by dispatching jobs', function () {
    Bus::fake();

    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => null]);
    $dir1 = Directory::factory()->create(['name' => 'DirA', 'parent_id' => null]);
    $dir2 = Directory::factory()->create(['name' => 'DirB', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.explorer.mass_move'), [
        'asset_ids'           => [],
        'directory_ids'       => [$dir1->id, $dir2->id],
        'target_directory_id' => $target->id,
    ]);

    $response->assertOk();
    Bus::assertDispatchedTimes(MoveDirectoryStructure::class, 2);
});

it('skips directory if it is an ancestor of the target (circular move)', function () {
    Bus::fake();

    $parent = Directory::factory()->create(['name' => 'Parent', 'parent_id' => null]);
    $child = Directory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

    // Trying to move parent INTO child — circular, must be skipped
    $response = $this->postJson(route('admin.dam.explorer.mass_move'), [
        'asset_ids'           => [],
        'directory_ids'       => [$parent->id],
        'target_directory_id' => $child->id,
    ]);

    $response->assertOk();
    Bus::assertNotDispatched(MoveDirectoryStructure::class);
});

it('skips root directory (id protected by isDeletable) in mass move', function () {
    Bus::fake();

    $root = Directory::find(1) ?? Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.explorer.mass_move'), [
        'asset_ids'           => [],
        'directory_ids'       => [$root->id],
        'target_directory_id' => $target->id,
    ]);

    $response->assertOk();
    Bus::assertNotDispatched(MoveDirectoryStructure::class);
});

it('returns 422 when target_directory_id is missing', function () {
    $response = $this->postJson(route('admin.dam.explorer.mass_move'), [
        'asset_ids'     => [],
        'directory_ids' => [1],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['target_directory_id']);
});

it('returns 422 when target_directory_id does not exist', function () {
    $response = $this->postJson(route('admin.dam.explorer.mass_move'), [
        'asset_ids'           => [],
        'directory_ids'       => [],
        'target_directory_id' => 999999,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['target_directory_id']);
});
