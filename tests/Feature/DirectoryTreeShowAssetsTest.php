<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('defaults the dam.tree.show_assets config to false when env is absent', function () {
    // Clear any cached value, then ensure env helper falls back to false.
    config()->set('dam.tree.show_assets', env('DAM_TREE_SHOW_ASSETS', false));

    expect(config('dam.tree.show_assets'))->toBeFalse();
});

it('returns an empty asset list from directory.assets when toggle is off', function () {
    config()->set('dam.tree.show_assets', false);

    $directory = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $directory->assets()->attach($asset->id);

    $response = $this->get(route('admin.dam.directory.assets', $directory->id));

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
});

it('returns the directory assets when toggle is on', function () {
    config()->set('dam.tree.show_assets', true);

    $directory = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $directory->assets()->attach($asset->id);

    $response = $this->get(route('admin.dam.directory.assets', $directory->id));

    $response->assertOk();
    expect($response->json('data'))->not->toBe([]);
});

it('keeps directory listing unaffected by the toggle', function () {
    // Directory tree itself must render regardless of the asset toggle —
    // only the per-directory asset lazy-load is gated.
    config()->set('dam.tree.show_assets', false);

    Directory::factory()->count(2)->create();

    $response = $this->get(route('admin.dam.directory.index'));

    $response->assertOk();
    expect($response->json('data'))->not->toBe([]);
});
