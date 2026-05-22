<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('role with inherit_children sees subdirectories created by another role', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $roleB = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.index']]);
    $roleC = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);

    $parent = Directory::factory()->create(['name' => 'InheritParent', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/'.$parent->name);

    // Grant parent to both roles
    DB::table('dam_directory_role')->insert([
        ['directory_id' => $parent->id, 'role_id' => $roleB->id, 'created_at' => now(), 'updated_at' => now()],
        ['directory_id' => $parent->id, 'role_id' => $roleC->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Enable inherit_children for role B only
    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $roleB->id],
        ['all_directories' => false, 'inherit_children' => true, 'created_at' => now(), 'updated_at' => now()]
    );

    // C creates a subdirectory
    $adminC = Admin::factory()->create(['role_id' => $roleC->id]);
    $this->actingAs($adminC, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $response = $this->postJson(route('admin.dam.directory.store'), [
        'name'      => 'CSubDir',
        'parent_id' => $parent->id,
    ]);
    $response->assertSuccessful();

    $cSubDir = Directory::where('name', 'CSubDir')->first();
    expect($cSubDir)->not->toBeNull();

    // B (with inherit_children) should be able to access CSubDir
    $adminB = Admin::factory()->create(['role_id' => $roleB->id]);
    $this->actingAs($adminB, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $service = app(DirectoryPermissionService::class);
    expect($service->canAccess($cSubDir->id))->toBeTrue();
    expect($service->canView($cSubDir->id))->toBeTrue();
});

it('role without inherit_children cannot access subdirectories created by another role', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $roleB = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.index']]);
    $roleC = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam.directory.store']]);

    $parent = Directory::factory()->create(['name' => 'NoInheritParent', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/'.$parent->name);

    DB::table('dam_directory_role')->insert([
        ['directory_id' => $parent->id, 'role_id' => $roleB->id, 'created_at' => now(), 'updated_at' => now()],
        ['directory_id' => $parent->id, 'role_id' => $roleC->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // inherit_children NOT set for B
    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $roleB->id],
        ['all_directories' => false, 'inherit_children' => false, 'created_at' => now(), 'updated_at' => now()]
    );

    // C creates a subdirectory
    $adminC = Admin::factory()->create(['role_id' => $roleC->id]);
    $this->actingAs($adminC, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $this->postJson(route('admin.dam.directory.store'), [
        'name'      => 'CSubDirNoInherit',
        'parent_id' => $parent->id,
    ])->assertSuccessful();

    $cSubDir = Directory::where('name', 'CSubDirNoInherit')->first();

    // B (without inherit_children) should NOT access CSubDir
    $adminB = Admin::factory()->create(['role_id' => $roleB->id]);
    $this->actingAs($adminB, 'admin');
    app(DirectoryPermissionService::class)->flush();

    $service = app(DirectoryPermissionService::class);
    expect($service->canAccess($cSubDir->id))->toBeFalse();
});

it('inherit_children expands all descendants at unlimited depth', function () {
    $roleB = Role::factory()->create(['permission_type' => 'custom']);
    $adminB = Admin::factory()->create(['role_id' => $roleB->id]);

    $root = Directory::create(['name' => 'IcDeepRoot', 'parent_id' => null]);
    $level1 = Directory::create(['name' => 'IcDeepL1', 'parent_id' => $root->id]);
    $level2 = Directory::create(['name' => 'IcDeepL2', 'parent_id' => $level1->id]);
    $level3 = Directory::create(['name' => 'IcDeepL3', 'parent_id' => $level2->id]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $root->id,
        'role_id'      => $roleB->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $roleB->id],
        ['all_directories' => false, 'inherit_children' => true, 'created_at' => now(), 'updated_at' => now()]
    );

    $this->actingAs($adminB, 'admin');
    app(DirectoryPermissionService::class)->flush();
    $service = app(DirectoryPermissionService::class);

    expect($service->canAccess($level1->id))->toBeTrue();
    expect($service->canAccess($level2->id))->toBeTrue();
    expect($service->canAccess($level3->id))->toBeTrue();
});

it('inherit_children does not bleed into unrelated directory trees', function () {
    $roleB = Role::factory()->create(['permission_type' => 'custom']);
    $adminB = Admin::factory()->create(['role_id' => $roleB->id]);

    $grantedRoot = Directory::create(['name' => 'IcGrantedTree', 'parent_id' => null]);
    $grantedChild = Directory::create(['name' => 'IcGrantedChild', 'parent_id' => $grantedRoot->id]);
    $otherRoot = Directory::create(['name' => 'IcOtherTree', 'parent_id' => null]);
    $otherChild = Directory::create(['name' => 'IcOtherChild', 'parent_id' => $otherRoot->id]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $grantedRoot->id,
        'role_id'      => $roleB->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $roleB->id],
        ['all_directories' => false, 'inherit_children' => true, 'created_at' => now(), 'updated_at' => now()]
    );

    $this->actingAs($adminB, 'admin');
    app(DirectoryPermissionService::class)->flush();
    $service = app(DirectoryPermissionService::class);

    expect($service->canAccess($grantedChild->id))->toBeTrue();
    expect($service->canAccess($otherRoot->id))->toBeFalse();
    expect($service->canAccess($otherChild->id))->toBeFalse();
});
