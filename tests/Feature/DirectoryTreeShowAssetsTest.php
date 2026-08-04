<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('defaults the dam.tree.show_assets config to false when env is absent', function () {

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

    config()->set('dam.tree.show_assets', false);

    Directory::factory()->count(2)->create();

    $response = $this->get(route('admin.dam.directory.index'));

    $response->assertOk();
    expect($response->json('data'))->not->toBe([]);
});
