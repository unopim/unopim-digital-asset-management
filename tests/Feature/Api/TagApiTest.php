<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('fetches a tag by id via api', function () {
    $tag = Tag::create(['name' => 'api-tag']);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.tags.get', $tag->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $tag->id);
});

it('returns 404 when tag not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.tags.get', 999999))
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// Helper — custom-role user granted only to a specific directory
// ---------------------------------------------------------------------------

function makeTagCustomHeaders(Directory $grantedDir): array
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

// ---------------------------------------------------------------------------
// Directory-permission gate tests (Task 6)
// ---------------------------------------------------------------------------

it('returns 403 when adding a tag to an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $granted = Directory::factory()->create();
    $headers = makeTagCustomHeaders($granted);

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.tag.add'), [
            'tag'      => 'newtag',
            'asset_id' => $asset->id,
        ])
        ->assertStatus(403);
});

it('returns 403 when removing a tag from an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $granted = Directory::factory()->create();
    $headers = makeTagCustomHeaders($granted);

    $this->withHeaders($headers)
        ->deleteJson(route('admin.api.dam.tag.delete'), [
            'tag'      => 'sometag',
            'asset_id' => $asset->id,
        ])
        ->assertStatus(403);
});
