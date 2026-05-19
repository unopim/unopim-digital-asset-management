<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\DAM\Models\Directory;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->loginAsAdmin();
});

/**
 * The DAM tab dispatches grants via the global `user.role.update.after` and
 * `user.role.create.after` events fired by Admin's RoleController. We exercise
 * that listener directly by dispatching the event with a faked request payload
 * — no need to drive the full HTTP form, which would couple the test to the
 * Admin package's blade structure.
 */
function dispatchRoleUpdateWith(array $payload, Role $role): void
{
    request()->replace($payload);

    Event::dispatch('user.role.update.after', [$role]);
}

it('syncs the submitted directory ids when the marker is present', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $dirOne = Directory::factory()->create();
    $dirTwo = Directory::factory()->create();

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [$dirOne->id, $dirTwo->id],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($stored)->toEqualCanonicalizing([$dirOne->id, $dirTwo->id]);
});

it('leaves existing grants untouched when the marker is absent', function () {
    $role = Role::factory()->create(['permission_type' => 'all']);
    $existing = Directory::factory()->create();

    DB::table('dam_directory_role')->insert([
        'directory_id' => $existing->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    // Submission without the marker — simulates a permission_type='all' save
    // where the tab's v-if hides the hidden input.
    dispatchRoleUpdateWith([
        'directories' => [],
    ], $role);

    expect(
        DB::table('dam_directory_role')->where('role_id', $role->id)->count()
    )->toBe(1);
});

it('falls back to the root directory when no selection is submitted', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);

    $root = Directory::query()->whereNull('parent_id')->orderBy('id')->first()
        ?? Directory::create(['name' => 'TestRoot', 'parent_id' => null]);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($stored)->toBe([$root->id]);
});

it('drops directory ids that no longer exist before insert', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $valid = Directory::factory()->create();
    $ghostId = $valid->id + 9_999;

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [$valid->id, $ghostId],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($stored)->toBe([$valid->id]);
});

it('replaces prior grants on subsequent saves rather than appending', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $first = Directory::factory()->create();
    $second = Directory::factory()->create();

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [$first->id],
    ], $role);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [$second->id],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($stored)->toBe([$second->id]);
});

it('syncs grants on role create.after with the same listener semantics', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $dir = Directory::factory()->create();

    request()->replace([
        'dam_directory_grants_managed' => '1',
        'directories'                  => [$dir->id],
    ]);

    Event::dispatch('user.role.create.after', [$role]);

    expect(
        DB::table('dam_directory_role')->where('role_id', $role->id)->pluck('directory_id')->all()
    )->toEqualCanonicalizing([$dir->id]);
});
