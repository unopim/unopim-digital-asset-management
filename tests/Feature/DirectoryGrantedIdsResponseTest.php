<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(DatabaseTransactions::class);

function grantRootAccess(Role $role, Directory $root): void
{
    DB::table('dam_directory_role')->insertOrIgnore([
        'directory_id' => $root->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
}

it('returns granted_directory_ids from create_structure and grants them to the creator', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);
    $root = Directory::factory()->create(['name' => 'StructRoot', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/'.$root->name);
    grantRootAccess($role, $root);

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.create_structure'), [
        'directory_id' => $root->id,
        'paths'        => ['a/b'],
    ]);

    $response->assertSuccessful();

    $dirA = Directory::where('name', 'a')->where('parent_id', $root->id)->first();
    $dirB = Directory::where('name', 'b')->where('parent_id', $dirA->id)->first();

    $grantedIds = $response->json('granted_directory_ids');

    expect($grantedIds)->toContain($dirA->id, $dirB->id);

    foreach ([$dirA->id, $dirB->id] as $id) {
        expect(
            DB::table('dam_directory_role')
                ->where('role_id', $role->id)
                ->where('directory_id', $id)
                ->exists()
        )->toBeTrue();
    }
});

it('create_structure omits already-existing directories from granted_directory_ids', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);
    $root = Directory::factory()->create(['name' => 'ExistingRoot', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/'.$root->name);
    grantRootAccess($role, $root);

    $existing = Directory::factory()->create(['name' => 'existing', 'parent_id' => $root->id]);

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.create_structure'), [
        'directory_id' => $root->id,
        'paths'        => ['existing'],
    ]);

    $response->assertSuccessful();

    expect($response->json('granted_directory_ids'))->toBe([]);
});

it('returns granted_directory_ids from folder upload when new subdirectories are created', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.asset.upload']]);
    $root = Directory::factory()->create(['name' => 'UploadRoot', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/'.$root->name);
    grantRootAccess($role, $root);

    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $this->actingAs($admin, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.assets.upload_folder'), [
        'files'           => [UploadedFile::fake()->image('photo.png', 4, 4)],
        'relative_paths'  => ['MyFolder/nested/photo.png'],
        'directory_id'    => $root->id,
        'preserve_root'   => '1',
    ]);

    $response->assertSuccessful();

    $nested = Directory::where('name', 'nested')->where('parent_id', '!=', null)->first();
    expect($nested)->not->toBeNull();

    $grantedIds = $response->json('granted_directory_ids');
    expect($grantedIds)->toContain($nested->id);

    expect(
        DB::table('dam_directory_role')
            ->where('role_id', $role->id)
            ->where('directory_id', $nested->id)
            ->exists()
    )->toBeTrue();
});
