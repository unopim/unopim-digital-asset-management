<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

function seedCountsFixture(): array
{
    $root = Directory::create(['name' => 'CntRoot', 'parent_id' => null]);
    $parent = Directory::create(['name' => 'CntParent', 'parent_id' => $root->id]);
    $leafA = Directory::create(['name' => 'CntLeafA', 'parent_id' => $parent->id]);
    $leafB = Directory::create(['name' => 'CntLeafB', 'parent_id' => $parent->id]);

    $parent->assets()->attach(Asset::factory()->create()->id);
    $leafA->assets()->attach([Asset::factory()->create()->id, Asset::factory()->create()->id]);

    return [$root, $parent, $leafA, $leafB];
}

it('returns subtree asset counts for the requested directory ids', function () {
    [$root, $parent, $leafA, $leafB] = seedCountsFixture();

    $response = $this->postJson(route('admin.dam.directory.asset_counts'), [
        'ids' => [$root->id, $parent->id, $leafA->id, $leafB->id],
    ]);

    $response->assertOk();

    expect($response->json("data.{$root->id}"))->toBe(3);
    expect($response->json("data.{$parent->id}"))->toBe(3);
    expect($response->json("data.{$leafA->id}"))->toBe(2);
    expect($response->json("data.{$leafB->id}"))->toBe(0);
});

it('returns an empty object when no ids are given', function () {
    $response = $this->postJson(route('admin.dam.directory.asset_counts'), ['ids' => []]);

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
});

it('ignores non-existent directory ids', function () {
    [$root, $parent, $leafA, $leafB] = seedCountsFixture();

    $response = $this->postJson(route('admin.dam.directory.asset_counts'), [
        'ids' => [$parent->id, 999999],
    ]);

    $response->assertOk();
    expect($response->json("data.{$parent->id}"))->toBe(3);
    expect($response->json('data.999999'))->toBeNull();
});

it('paginates children and reports has_more', function () {
    $parent = Directory::factory()->create();
    Directory::factory()->count(3)->create(['parent_id' => $parent->id]);

    $first = $this->get(route('admin.dam.directory.children', $parent->id).'?offset=0&limit=2');
    $first->assertOk();
    $first->assertJsonCount(2, 'data');
    expect($first->json('has_more'))->toBeTrue();

    $second = $this->get(route('admin.dam.directory.children', $parent->id).'?offset=2&limit=2');
    $second->assertOk();
    $second->assertJsonCount(1, 'data');
    expect($second->json('has_more'))->toBeFalse();
});

it('does not embed asset counts in the children response', function () {
    $parent = Directory::factory()->create();
    Directory::factory()->count(2)->create(['parent_id' => $parent->id]);

    $response = $this->get(route('admin.dam.directory.children', $parent->id));

    $response->assertOk();
    foreach ($response->json('data') as $child) {
        expect($child)->not->toHaveKey('assets_total_count');
        expect($child)->toHaveKey('has_children');
    }
});
