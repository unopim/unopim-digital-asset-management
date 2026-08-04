<?php

declare(strict_types=1);

use Webkul\DAM\Models\Directory;

function seedAncestorFixture(): array
{
    $root = Directory::create(['name' => 'AncRoot',   'parent_id' => null]);
    $parent = Directory::create(['name' => 'AncParent', 'parent_id' => $root->id]);
    $child = Directory::create(['name' => 'AncChild',  'parent_id' => $parent->id]);

    return ['root' => $root, 'parent' => $parent, 'child' => $child];
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('rejects unauthenticated requests to POST /directory/paths', function () {
    auth('admin')->logout();

    $response = $this->postJson(route('admin.dam.directory.paths'), ['ids' => [1]]);

    expect(in_array($response->status(), [302, 401, 403], true))->toBeTrue();
});

it('returns root, parent, and child when given the child id', function () {
    ['root' => $root, 'parent' => $parent, 'child' => $child] = seedAncestorFixture();

    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [$child->id],
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data']);

    $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain($root->id);
    expect($ids)->toContain($parent->id);
    expect($ids)->toContain($child->id);
});

it('returns nodes ordered root-first (ascending _lft)', function () {
    ['root' => $root, 'parent' => $parent, 'child' => $child] = seedAncestorFixture();

    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [$child->id],
    ]);

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

    expect(array_search($root->id, $ids, true))->toBeLessThan(array_search($parent->id, $ids, true));
    expect(array_search($parent->id, $ids, true))->toBeLessThan(array_search($child->id, $ids, true));
});

it('each node in the response contains id, name, parent_id, and has_children', function () {
    ['root' => $root, 'parent' => $parent, 'child' => $child] = seedAncestorFixture();

    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [$child->id],
    ]);

    $response->assertOk();

    foreach ($response->json('data') as $node) {
        expect($node)->toHaveKey('id');
        expect($node)->toHaveKey('name');
        expect($node)->toHaveKey('parent_id');
        expect($node)->toHaveKey('has_children');
    }
});

it('deduplicates ancestor chains when multiple ids share ancestors', function () {
    $root = Directory::create(['name' => 'DeduRoot',   'parent_id' => null]);
    $parent = Directory::create(['name' => 'DeduParent', 'parent_id' => $root->id]);
    $childA = Directory::create(['name' => 'DeduChildA', 'parent_id' => $parent->id]);
    $childB = Directory::create(['name' => 'DeduChildB', 'parent_id' => $parent->id]);

    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [$childA->id, $childB->id],
    ]);

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect(count(array_keys($ids, $root->id, true)))->toBe(1);
    expect(count(array_keys($ids, $parent->id, true)))->toBe(1);
});

it('returns an empty data array when ids is an empty array', function () {
    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [],
    ]);

    $response->assertOk();
    $response->assertExactJson(['data' => []]);
});

it('returns an empty data array when all ids are non-existent', function () {
    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => [999999, 999998],
    ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('returns 422 when the ids key is missing', function () {
    $response = $this->postJson(route('admin.dam.directory.paths'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);
});

it('returns 422 when ids contains non-integer values', function () {
    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => ['abc'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ids.0']);
});

it('returns 422 when ids is not an array', function () {
    $response = $this->postJson(route('admin.dam.directory.paths'), [
        'ids' => 'not-an-array',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ids']);
});
