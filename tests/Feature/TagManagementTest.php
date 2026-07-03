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

it('paginates the autocomplete tag list', function () {
    foreach (range(1, 30) as $i) {
        Tag::create(['name' => sprintf('tag-%02d', $i)]);
    }

    $page1 = $this->getJson(route('admin.dam.tags.list'));
    $page1->assertOk();
    expect($page1->json('data'))->toHaveCount(25);
    expect($page1->json('has_more'))->toBeTrue();
    expect((int) $page1->json('last_page'))->toBe(2);

    $page2 = $this->getJson(route('admin.dam.tags.list', ['page' => 2]));
    $page2->assertOk();
    expect($page2->json('data'))->toHaveCount(5);
    expect($page2->json('has_more'))->toBeFalse();
});

it('honours a custom per_page on the autocomplete tag list', function () {
    foreach (range(1, 12) as $i) {
        Tag::create(['name' => sprintf('size-%02d', $i)]);
    }

    $response = $this->getJson(route('admin.dam.tags.list', ['per_page' => 5]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('has_more'))->toBeTrue();
});

it('filters the autocomplete tag list by query', function () {
    Tag::create(['name' => 'alpha']);
    Tag::create(['name' => 'alphabet']);
    Tag::create(['name' => 'beta']);

    $response = $this->getJson(route('admin.dam.tags.list', ['query' => 'alph']));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();

    expect($names)->toContain('alpha')
        ->toContain('alphabet')
        ->not->toContain('beta');
});

it('exposes the explorer translation keys introduced for the UI changes', function () {
    // These must resolve to real strings (not echo the key back) so the
    // blade @lang() references render correctly instead of leaking raw keys.
    foreach ([
        'dam::app.admin.explorer.action-completed',
        'dam::app.admin.explorer.bookmarks.remove',
        'dam::app.admin.explorer.bookmarks.empty',
    ] as $key) {
        expect(trans($key))->not->toBe($key);
    }

    expect(trans('dam::app.admin.explorer.mass-actions.move-done'))->toContain(':source');
    expect(trans('dam::app.admin.explorer.mass-actions.move-done'))->toContain(':destination');
    expect(trans('dam::app.admin.explorer.mass-actions.deleted-assets'))->toContain(':source');
});
