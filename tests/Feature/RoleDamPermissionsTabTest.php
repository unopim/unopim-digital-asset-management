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

it('saves all_directories true in dam_role_settings when submitted', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_all_directories'          => '1',
        'directories'                  => [],
    ], $role);

    expect(
        DB::table('dam_role_settings')
            ->where('role_id', $role->id)
            ->where('all_directories', true)
            ->exists()
    )->toBeTrue();
});

it('clears all_directories in dam_role_settings when unchecked', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);

    DB::table('dam_role_settings')->insert([
        'role_id'         => $role->id,
        'all_directories' => true,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_all_directories'          => '0',
        'directories'                  => [],
    ], $role);

    $setting = DB::table('dam_role_settings')->where('role_id', $role->id)->first();
    expect((bool) $setting?->all_directories)->toBeFalse();
});

it('saves inherit_children true in dam_role_settings when submitted', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_inherit_children'         => '1',
        'directories'                  => [],
    ], $role);

    expect(
        DB::table('dam_role_settings')
            ->where('role_id', $role->id)
            ->where('inherit_children', true)
            ->exists()
    )->toBeTrue();
});

it('clears inherit_children in dam_role_settings when unchecked', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);

    DB::table('dam_role_settings')->insert([
        'role_id'          => $role->id,
        'all_directories'  => false,
        'inherit_children' => true,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_inherit_children'         => '0',
        'directories'                  => [],
    ], $role);

    $setting = DB::table('dam_role_settings')->where('role_id', $role->id)->first();
    expect((bool) $setting?->inherit_children)->toBeFalse();
});

it('preserves explicit child grants when inherit_children strips expanded descendants', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $parent = Directory::create(['name' => 'PreserveParent', 'parent_id' => null]);
    $child = Directory::create(['name' => 'PreserveChild', 'parent_id' => $parent->id]);
    $grandchild = Directory::create(['name' => 'PreserveGrand', 'parent_id' => $child->id]);

    // Seed: parent + child both explicitly in DB (child was auto-granted by User B)
    DB::table('dam_directory_role')->insert([
        ['directory_id' => $parent->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()],
        ['directory_id' => $child->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Form submits parent + child + grandchild (grandchild added by inherit expansion)
    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_inherit_children'         => '1',
        'directories'                  => [$parent->id, $child->id, $grandchild->id],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->sort()->values()->all();

    expect($stored)->toEqualCanonicalizing([$parent->id, $child->id]);
});

it('strips inherit-expanded descendants when no prior grants exist', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $root = Directory::create(['name' => 'StripFreshRoot', 'parent_id' => null]);
    $child = Directory::create(['name' => 'StripFreshChild', 'parent_id' => $root->id]);

    dispatchRoleUpdateWith([
        'dam_directory_grants_managed' => '1',
        'dam_inherit_children'         => '1',
        'directories'                  => [$root->id, $child->id],
    ], $role);

    $stored = DB::table('dam_directory_role')
        ->where('role_id', $role->id)
        ->pluck('directory_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($stored)->toBe([$root->id]);
});
