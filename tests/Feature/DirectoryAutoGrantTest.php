<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function makeRootFakeDir(Directory $root): void
{
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/'.$root->name);
}

it('auto-grants new subdirectory to every role that has the parent directory granted', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);
    $root = Directory::factory()->create(['name' => 'AutoGrantRoot', 'parent_id' => null]);
    makeRootFakeDir($root);

    DB::table('dam_directory_role')->insertOrIgnore([
        'directory_id' => $root->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.store'), [
        'name'      => 'AutoGrantDir',
        'parent_id' => $root->id,
    ]);

    $response->assertSuccessful();

    $newDir = Directory::where('name', 'AutoGrantDir')->first();
    expect($newDir)->not->toBeNull();

    expect(
        DB::table('dam_directory_role')
            ->where('role_id', $role->id)
            ->where('directory_id', $newDir->id)
            ->exists()
    )->toBeTrue();
});

it('skips auto-grant for roles that have all_directories enabled even when parent is granted', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);
    $root = Directory::factory()->create(['name' => 'AllDirRoot', 'parent_id' => null]);
    makeRootFakeDir($root);

    DB::table('dam_directory_role')->insertOrIgnore([
        'directory_id' => $root->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $role->id],
        ['all_directories' => true, 'created_at' => now(), 'updated_at' => now()]
    );

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.store'), [
        'name'      => 'AllDirGrantDir',
        'parent_id' => $root->id,
    ]);

    $response->assertSuccessful();

    $newDir = Directory::where('name', 'AllDirGrantDir')->first();
    expect($newDir)->not->toBeNull();

    expect(
        DB::table('dam_directory_role')
            ->where('role_id', $role->id)
            ->where('directory_id', $newDir->id)
            ->exists()
    )->toBeFalse();
});

it('does not auto-grant when no role has the parent directory granted', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'all']);
    $root = Directory::factory()->create(['name' => 'AllPermRoot', 'parent_id' => null]);
    makeRootFakeDir($root);

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.store'), [
        'name'      => 'AllPermDir',
        'parent_id' => $root->id,
    ]);

    $response->assertSuccessful();

    $newDir = Directory::where('name', 'AllPermDir')->first();
    expect($newDir)->not->toBeNull();

    expect(
        DB::table('dam_directory_role')
            ->where('role_id', $role->id)
            ->where('directory_id', $newDir->id)
            ->exists()
    )->toBeFalse();
});
