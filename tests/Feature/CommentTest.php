<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;
use Webkul\User\Models\Admin;

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

it('should create a threaded reply comment', function () {
    $asset = Asset::factory()->create();
    $parentComment = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
    ]);

    $payload = [
        'comments'  => 'This is a reply to the parent comment',
        'parent_id' => $parentComment->id,
    ];

    $response = $this->postJson(route('admin.dam.asset.comment.store', $asset->id), $payload);
    $response->assertOk();
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.asset.comments.create.create-success'),
    ]);

    $this->assertDatabaseHas('dam_asset_comments', [
        'comments'     => 'This is a reply to the parent comment',
        'parent_id'    => $parentComment->id,
        'dam_asset_id' => $asset->id,
    ]);
});

it('should validate comment minimum length', function () {
    $asset = Asset::factory()->create();

    $response = $this->postJson(route('admin.dam.asset.comment.store', $asset->id), [
        'comments' => 'A',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['comments']);
});

it('should validate comment maximum length', function () {
    $asset = Asset::factory()->create();

    $response = $this->postJson(route('admin.dam.asset.comment.store', $asset->id), [
        'comments' => str_repeat('a', 1001),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['comments']);
});

it('should validate comment is required', function () {
    $asset = Asset::factory()->create();

    $response = $this->postJson(route('admin.dam.asset.comment.store', $asset->id), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['comments']);
});

it('should validate name and value when updating a comment', function () {
    $asset = Asset::factory()->create();

    $response = $this->putJson(route('admin.dam.asset.comment.update', $asset->id), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'value']);
});

it('should return user info with timezone', function () {
    $admin = Admin::first();

    $response = $this->getJson(route('admin.dam.asset.comments.get_user_info', $admin->id));

    $response->assertOk()
        ->assertJsonStructure([
            'user' => ['name', 'status'],
            'timezone',
        ]);
});
