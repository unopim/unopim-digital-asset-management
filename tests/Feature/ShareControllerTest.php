<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;

uses(DatabaseTransactions::class);

beforeEach(function () {
    DB::table('dam_shares')->delete();
    $this->loginAsAdmin();
});

it('lists the shares manage page', function () {
    $this->get(route('admin.dam.shares.index'))
        ->assertOk()
        ->assertSeeText(trans('dam::app.admin.dam.share.index.title'));
});

it('creates a share for an asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->post(route('admin.dam.shares.store'), [
        'share_type'  => Share::TYPE_ASSET,
        'target_id'   => $asset->id,
        'expiry_days' => 7,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('share.share_type', Share::TYPE_ASSET)
        ->assertJsonPath('share.target_id', $asset->id);

    $this->assertDatabaseHas('dam_shares', [
        'share_type' => Share::TYPE_ASSET,
        'target_id'  => $asset->id,
    ]);
});

it('creates a share for a directory', function () {
    $directory = Directory::factory()->create();

    $this->post(route('admin.dam.shares.store'), [
        'share_type'  => Share::TYPE_DIRECTORY,
        'target_id'   => $directory->id,
        'expiry_days' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('share.share_type', Share::TYPE_DIRECTORY);
});

it('creates a non-expiring share when no_expiry is true', function () {
    $asset = Asset::factory()->create();

    $response = $this->post(route('admin.dam.shares.store'), [
        'share_type' => Share::TYPE_ASSET,
        'target_id'  => $asset->id,
        'no_expiry'  => 1,
    ]);

    $response->assertOk();
    $share = Share::query()->where('target_id', $asset->id)->first();
    expect($share->expires_at)->toBeNull();
});

it('rejects share creation for a non-existent target', function () {
    $this->post(route('admin.dam.shares.store'), [
        'share_type'  => Share::TYPE_ASSET,
        'target_id'   => 999999,
        'expiry_days' => 7,
    ])->assertNotFound();
});

it('rejects share creation with invalid share_type', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.shares.store'), [
        'share_type' => 'banana',
        'target_id'  => $asset->id,
    ])->assertUnprocessable();
});

it('revokes a share', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->create();

    $response = $this->delete(route('admin.dam.shares.destroy', $share->id));

    $response->assertOk()->assertJsonPath('success', true);
    expect($share->fresh()->revoked_at)->not->toBeNull();
});

it('returns 404 when revoking a non-existent share', function () {
    $this->delete(route('admin.dam.shares.destroy', 999999))
        ->assertNotFound();
});

it('lists active shares for a target', function () {
    $asset = Asset::factory()->create();
    Share::factory()->forAsset($asset->id)->create();
    Share::factory()->forAsset($asset->id)->revoked()->create();

    $response = $this->get(route('admin.dam.shares.active_for_target', [
        'type'     => Share::TYPE_ASSET,
        'targetId' => $asset->id,
    ]));

    $response->assertOk()->assertJsonPath('success', true);
    expect($response->json('shares'))->toHaveCount(1);
});
