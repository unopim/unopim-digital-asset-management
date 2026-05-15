<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    $this->headers = $this->getAuthenticationHeaders();
});

it('returns the directory tree via api index', function () {
    Directory::factory()->create(['name' => 'Root']);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.index'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data']);
});

it('creates a new directory via api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/ApiRoot');

    $parent = Directory::factory()->create(['name' => 'ApiRoot', 'parent_id' => null]);

    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.directory.store'), [
            'name'      => 'API Child',
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('dam_directories', ['name' => 'API Child', 'parent_id' => $parent->id]);
});

it('returns a single directory by id via api', function () {
    $directory = Directory::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.get', $directory->id));

    $response->assertOk()->assertJsonPath('success', true);
});

it('returns 404 when directory not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.get', 999999))
        ->assertStatus(404);
});

it('renames a directory and dispatches rename job via api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/OldApiDir');
    Bus::fake();

    $directory = Directory::factory()->create(['name' => 'OldApiDir', 'parent_id' => null]);

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.directory.update', $directory->id), [
            'name' => 'RenamedApiDir',
        ]);

    $response->assertOk()->assertJsonPath('success', true);
    Bus::assertDispatched(RenameDirectoryJob::class);
});

it('returns 404 when updating a non-existent directory via api', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.directory.update', 999999), ['name' => 'X'])
        ->assertStatus(404);
});

it('dispatches delete job for a deletable directory via api', function () {
    Bus::fake();
    $directory = Directory::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.directory.delete', $directory->id));

    $response->assertStatus(202)->assertJsonPath('success', true);
    Bus::assertDispatched(DeleteDirectoryJob::class);
});

it('returns 404 when deleting a non-existent directory via api', function () {
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.directory.delete', 999999))
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// Permission-gating tests (Task 3)
// ---------------------------------------------------------------------------

function makeCustomDirApiHeaders(Directory $grantedDir): array
{
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $grantedDir->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    $headers = test()->getAuthenticationHeaders();
    Admin::latest('id')->first()->update(['role_id' => $role->id]);
    app(DirectoryPermissionService::class)->flush();

    return $headers;
}

it('returns 403 when fetching a denied directory via api', function () {
    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();
    $headers = makeCustomDirApiHeaders($granted);

    $this->withHeaders($headers)
        ->getJson(route('admin.api.dam.directory.get', $denied->id))
        ->assertStatus(403);
});

it('returns 403 when creating a directory under a denied parent via api', function () {
    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();
    $headers = makeCustomDirApiHeaders($granted);

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.directory.store'), [
            'name'      => 'new-dir',
            'parent_id' => $denied->id,
        ])
        ->assertStatus(403);
});

it('returns 403 when updating a denied directory via api', function () {
    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();
    $headers = makeCustomDirApiHeaders($granted);

    $this->withHeaders($headers)
        ->putJson(route('admin.api.dam.directory.update', $denied->id), [
            'name' => 'renamed',
        ])
        ->assertStatus(403);
});

it('returns 403 when deleting a denied directory via api', function () {
    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();
    $headers = makeCustomDirApiHeaders($granted);

    $this->withHeaders($headers)
        ->deleteJson(route('admin.api.dam.directory.delete', $denied->id))
        ->assertStatus(403);
});
