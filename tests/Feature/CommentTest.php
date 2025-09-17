<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return a comment by id', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
    ]);

    $response = $this->getJson(route('admin.dam.asset.comments.index', $comment->id));
    $response->assertOk();
    $response->assertJson([
        'id'           => $comment->id,
        'admin_id'     => $comment->admin_id,
        'parent_id'    => $comment->parent_id,
        'comments'     => $comment->comments,
        'dam_asset_id' => $asset->id,
    ]);
});

it('should create a new comment', function () {
    $asset = Asset::factory()->create();

    $payload = [
        'comments'  => 'This is a test comment',
        'parent_id' => null,
    ];

    $response = $this->postJson(route('admin.dam.asset.comment.store', $asset->id), $payload);
    $response->assertStatus(200);
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.asset.comments.create.create-success'),
    ]);

    $this->assertDatabaseHas('dam_asset_comments', [
        'comments'     => 'This is a test comment',
        'dam_asset_id' => $asset->id,
        'admin_id'     => auth()->id(),
    ]);
});

it('should delete a comment', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
    ]);

    $payload = ['id' => $comment->id];

    $response = $this->deleteJson(route('admin.dam.asset.comment.delete', $asset->id), $payload);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.comments.index.delete-success'),
    ]);

    $this->assertDatabaseMissing('dam_asset_comments', [
        'id' => $comment->id,
    ]);
});
