<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('fetches a comment by id via api', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.comment.get', $comment->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $comment->id);
});

it('returns 404 when comment not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.comment.get', 999999))
        ->assertStatus(404);
});

it('creates a new comment via api', function () {
    $asset = Asset::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.comment.store'), [
            'comments'     => 'Looks good',
            'dam_asset_id' => $asset->id,
        ]);

    $response->assertStatus(201)->assertJsonStructure(['comment']);

    $this->assertDatabaseHas('dam_asset_comments', [
        'comments'     => 'Looks good',
        'dam_asset_id' => $asset->id,
    ]);
});

it('validates comment creation fields via api', function () {
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.comment.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['comments', 'dam_asset_id']);
});

it('updates an existing comment via api', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.comment.update', $comment->id), [
            'comments' => 'updated text',
        ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('dam_asset_comments', [
        'id'       => $comment->id,
        'comments' => 'updated text',
    ]);
});

it('returns 404 when updating a non-existent comment via api', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.comment.update', 999999), ['comments' => 'x'])
        ->assertStatus(404);
});

it('deletes a comment via api', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.comment.delete', $comment->id));

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseMissing('dam_asset_comments', ['id' => $comment->id]);
});

it('returns 404 when deleting a non-existent comment via api', function () {
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.comment.delete', 999999))
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// Helper — custom-role user granted only to a specific directory
// ---------------------------------------------------------------------------

function makeCommentCustomHeaders(Directory $grantedDir): array
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
// Directory-permission gate tests (Task 5)
// ---------------------------------------------------------------------------

it('returns 403 when fetching a comment for an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);
    $granted = Directory::factory()->create();
    $headers = makeCommentCustomHeaders($granted);

    $this->withHeaders($headers)
        ->getJson(route('admin.api.dam.comment.get', $comment->id))
        ->assertStatus(403);
});

it('returns 403 when creating a comment for an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $granted = Directory::factory()->create();
    $headers = makeCommentCustomHeaders($granted);

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.comment.store'), [
            'comments'     => 'test comment text',
            'dam_asset_id' => $asset->id,
        ])
        ->assertStatus(403);
});

it('returns 403 when updating a comment for an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);
    $granted = Directory::factory()->create();
    $headers = makeCommentCustomHeaders($granted);

    $this->withHeaders($headers)
        ->putJson(route('admin.api.dam.comment.update', $comment->id), [
            'comments' => 'updated text',
        ])
        ->assertStatus(403);
});

it('returns 403 when deleting a comment for an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);
    $granted = Directory::factory()->create();
    $headers = makeCommentCustomHeaders($granted);

    $this->withHeaders($headers)
        ->deleteJson(route('admin.api.dam.comment.delete', $comment->id))
        ->assertStatus(403);
});
