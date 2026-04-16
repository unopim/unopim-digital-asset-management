<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;

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
