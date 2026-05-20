<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->service = app(DirectoryPermissionService::class);
});

it('bypasses filtering when no admin is authenticated (API/CLI/anon)', function () {
    $this->service->flush();

    // No admin guard ⇒ bypass: filter is a no-op so API/CLI/anonymous code paths
    // (which never go through the admin web guard) are unaffected.
    expect($this->service->bypass())->toBeTrue();
});

it('returns true for every directory when role permission_type is all', function () {
    $allRole = Role::factory()->create(['permission_type' => 'all']);
    $admin = Admin::factory()->create(['role_id' => $allRole->id]);
    $this->actingAs($admin, 'admin');
    $this->service->flush();

    $directory = Directory::factory()->create();

    expect($this->service->bypass())->toBeTrue();
    expect($this->service->canView($directory->id))->toBeTrue();
    expect($this->service->canView(99999))->toBeTrue();
});

it('returns true only for directories granted to the role for custom roles', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    $this->service->flush();

    $granted = Directory::factory()->create();
    $denied = Directory::factory()->create();

    DB::table('dam_directory_role')->insert([
        'directory_id' => $granted->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    expect($this->service->canView($granted->id))->toBeTrue();
    expect($this->service->canView($denied->id))->toBeFalse();
});

it('memoises viewableIds within the same request', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    $this->service->flush();

    $dir = Directory::factory()->create();
    DB::table('dam_directory_role')->insert([
        'directory_id' => $dir->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->service->viewableIds();
    $this->service->viewableIds();
    $this->service->viewableIds();

    $count = collect(DB::getQueryLog())->filter(
        fn ($q) => str_contains(strtolower($q['query']), 'dam_directory_role')
    )->count();

    expect($count)->toBe(1);
});

it('exposes ancestors of granted directories as viewable but not accessible', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    $this->service->flush();

    // Build Root → AudioVideo → Audio (deep grant on the leaf only)
    $root = Directory::create(['name' => 'AclTestRoot', 'parent_id' => null]);
    $av = Directory::create(['name' => 'AudioVideo', 'parent_id' => $root->id]);
    $audio = Directory::create(['name' => 'Audio', 'parent_id' => $av->id]);
    $other = Directory::create(['name' => 'Other', 'parent_id' => $root->id]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $audio->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $viewable = $this->service->viewableIds();

    // Tree visibility: leaf + ancestors
    expect($viewable)->toContain($audio->id);
    expect($viewable)->toContain($av->id);
    expect($viewable)->toContain($root->id);
    // Sibling under root is NOT visible
    expect($viewable)->not->toContain($other->id);

    // canView is permissive (tree)
    expect($this->service->canView($av->id))->toBeTrue();
    expect($this->service->canView($audio->id))->toBeTrue();

    // canAccess is strict (only directly granted)
    expect($this->service->canAccess($audio->id))->toBeTrue();
    expect($this->service->canAccess($av->id))->toBeFalse();
    expect($this->service->canAccess($root->id))->toBeFalse();
});

it('does not bypass for an api-guard user with a custom role', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'api');
    $this->service->flush();

    expect($this->service->bypass())->toBeFalse();
});

it('bypasses for an api-guard user with permission_type all', function () {
    $role = Role::factory()->create(['permission_type' => 'all']);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'api');
    $this->service->flush();

    expect($this->service->bypass())->toBeTrue();
});

it('bypasses for a custom role with all_directories set in dam_role_settings', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $admin = Admin::factory()->create(['role_id' => $role->id]);

    DB::table('dam_role_settings')->insert([
        'role_id'         => $role->id,
        'all_directories' => true,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->actingAs($admin, 'admin');
    $this->service->flush();

    expect($this->service->bypass())->toBeTrue();
});

it('does not bypass for a custom role without all_directories', function () {
    $role = Role::factory()->create(['permission_type' => 'custom']);
    $admin = Admin::factory()->create(['role_id' => $role->id]);

    $this->actingAs($admin, 'admin');
    $this->service->flush();

    expect($this->service->bypass())->toBeFalse();
});
