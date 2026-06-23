<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('lists tags with their asset usage count in the datagrid', function () {
    $tag = Tag::create(['name' => 'summer']);
    $asset = Asset::factory()->create();
    $asset->tags()->attach($tag->id);

    Tag::create(['name' => 'unused']);

    // The datagrid feed only returns JSON for XHR requests (request()->ajax()).
    $response = $this->getJson(route('admin.dam.tags.index'), ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk();

    $records = collect($response->json('records'));

    expect((int) $records->firstWhere('name', 'summer')['assets_count'])->toBe(1);
    expect((int) $records->firstWhere('name', 'unused')['assets_count'])->toBe(0);
});

it('creates a new tag', function () {
    $response = $this->postJson(route('admin.dam.tags.store'), ['name' => 'new-tag']);

    $response->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('dam_tags', ['name' => 'new-tag']);
});

it('requires a name when creating a tag', function () {
    $this->postJson(route('admin.dam.tags.store'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('rejects a duplicate tag name (case-insensitive)', function () {
    Tag::create(['name' => 'Summer']);

    $this->postJson(route('admin.dam.tags.store'), ['name' => 'summer'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('renames an existing tag', function () {
    $tag = Tag::create(['name' => 'old-name']);

    $this->putJson(route('admin.dam.tags.update', $tag->id), ['name' => 'new-name'])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('dam_tags', ['id' => $tag->id, 'name' => 'new-name']);
    $this->assertDatabaseMissing('dam_tags', ['name' => 'old-name']);
});

it('allows a tag to keep its own name on update', function () {
    $tag = Tag::create(['name' => 'keep']);

    $this->putJson(route('admin.dam.tags.update', $tag->id), ['name' => 'keep'])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('deletes a tag and detaches it from assets', function () {
    $tag = Tag::create(['name' => 'removable']);
    $asset = Asset::factory()->create();
    $asset->tags()->attach($tag->id);

    $this->deleteJson(route('admin.dam.tags.destroy', $tag->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('dam_tags', ['id' => $tag->id]);
    $this->assertDatabaseMissing('dam_asset_tag', ['tag_id' => $tag->id]);
});

it('mass deletes selected tags', function () {
    $a = Tag::create(['name' => 'a']);
    $b = Tag::create(['name' => 'b']);
    $c = Tag::create(['name' => 'c']);

    $this->postJson(route('admin.dam.tags.mass_delete'), ['indices' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('dam_tags', ['id' => $a->id]);
    $this->assertDatabaseMissing('dam_tags', ['id' => $b->id]);
    $this->assertDatabaseHas('dam_tags', ['id' => $c->id]);
});

it('returns the tag list for autocomplete', function () {
    Tag::create(['name' => 'alpha']);
    Tag::create(['name' => 'beta']);

    $response = $this->getJson(route('admin.dam.tags.list'));

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())
        ->toContain('alpha')
        ->toContain('beta');
});

it('returns 404 when updating a missing tag', function () {
    $this->putJson(route('admin.dam.tags.update', 99999), ['name' => 'x'])
        ->assertStatus(404);
});
