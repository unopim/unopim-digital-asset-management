<?php

use Illuminate\Support\Facades\Event;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('assigns tags to multiple assets at once', function () {
    $assets = Asset::factory()->count(3)->create();

    $response = $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => $assets->pluck('id')->all(),
        'tags'    => ['campaign', 'q3'],
    ]);

    $response->assertOk()->assertJson(['success' => true, 'count' => 3]);

    foreach ($assets as $asset) {
        expect($asset->refresh()->tags->pluck('name')->all())
            ->toContain('campaign')
            ->toContain('q3');
    }

    $this->assertDatabaseHas('dam_tags', ['name' => 'campaign']);
    $this->assertDatabaseHas('dam_tags', ['name' => 'q3']);
});

it('creates new tags on the fly when assigning', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['brand-new'],
    ])->assertOk();

    $this->assertDatabaseHas('dam_tags', ['name' => 'brand-new']);
});

it('reuses an existing tag instead of creating a duplicate', function () {
    $tag = Tag::create(['name' => 'existing']);
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['existing'],
    ])->assertOk();

    expect(Tag::where('name', 'existing')->count())->toBe(1);
    expect($asset->refresh()->tags->first()->id)->toBe($tag->id);
});

it('matches existing tags case-insensitively', function () {
    Tag::create(['name' => 'Summer']);
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['summer'],
    ])->assertOk();

    expect(Tag::whereRaw('LOWER(name) = ?', ['summer'])->count())->toBe(1);
});

it('is additive and keeps tags the asset already has', function () {
    $asset = Asset::factory()->create();
    $existing = Tag::create(['name' => 'keep-me']);
    $asset->tags()->attach($existing->id);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['added'],
    ])->assertOk();

    $names = $asset->refresh()->tags->pluck('name')->all();

    expect($names)->toContain('keep-me')->toContain('added');
});

it('does not create duplicate pivot rows when a tag is already attached', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'once']);
    $asset->tags()->attach($tag->id);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['once'],
    ])->assertOk();

    expect($asset->refresh()->tags()->where('tag_id', $tag->id)->count())->toBe(1);
});

it('dispatches the tag sync event per asset', function () {
    Event::fake(['core.model.proxy.sync.tag']);

    $assets = Asset::factory()->count(2)->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => $assets->pluck('id')->all(),
        'tags'    => ['x'],
    ])->assertOk();

    Event::assertDispatchedTimes('core.model.proxy.sync.tag', 2);
});

it('validates that asset indices are required', function () {
    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'tags' => ['x'],
    ])->assertStatus(422)->assertJsonValidationErrors(['indices']);
});

it('validates that tags are required', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
    ])->assertStatus(422)->assertJsonValidationErrors(['tags']);
});

it('rejects tag names longer than 100 characters', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => [str_repeat('a', 101)],
    ])->assertStatus(422)->assertJsonValidationErrors(['tags.0']);
});
