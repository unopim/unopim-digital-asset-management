<?php

use Illuminate\Support\Facades\Event;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should add a new tag to an asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->postJson(route('admin.dam.assets.tag'), [
        'tag'      => 'new-tag',
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => trans('Tag attached successfully'),
        ]);

    $this->assertDatabaseHas('dam_tags', ['name' => 'new-tag']);
    expect($asset->refresh()->tags->pluck('name')->toArray())->toContain('new-tag');
});

it('should attach an existing tag to an asset', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'existing-tag']);

    $response = $this->postJson(route('admin.dam.assets.tag'), [
        'tag'      => 'existing-tag',
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    expect($asset->refresh()->tags)->toHaveCount(1);
    expect($asset->tags->first()->id)->toBe($tag->id);
});

it('should not attach a duplicate tag to an asset', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'duplicate-tag']);
    $asset->tags()->attach($tag->id);

    $response = $this->postJson(route('admin.dam.assets.tag'), [
        'tag'      => 'duplicate-tag',
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => trans('dam::app.admin.dam.asset.edit.tag-already-exists'),
        ]);
});

it('should remove a tag from an asset', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'removable-tag']);
    $asset->tags()->attach($tag->id);

    $response = $this->postJson(route('admin.dam.assets.remove-tag'), [
        'tag'      => 'removable-tag',
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => trans('Tag removed from asset successfully'),
        ]);

    expect($asset->refresh()->tags)->toHaveCount(0);
});

it('should dispatch tag sync event when adding a tag', function () {
    Event::fake(['core.model.proxy.sync.tag']);

    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.tag'), [
        'tag'      => 'event-tag',
        'asset_id' => $asset->id,
    ]);

    Event::assertDispatched('core.model.proxy.sync.tag');
});

it('should dispatch tag sync event when removing a tag', function () {
    Event::fake(['core.model.proxy.sync.tag']);

    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'event-remove-tag']);
    $asset->tags()->attach($tag->id);

    $this->postJson(route('admin.dam.assets.remove-tag'), [
        'tag'      => 'event-remove-tag',
        'asset_id' => $asset->id,
    ]);

    Event::assertDispatched('core.model.proxy.sync.tag');
});

it('should validate tag field is required', function () {
    $asset = Asset::factory()->create();

    $response = $this->postJson(route('admin.dam.assets.tag'), [
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tag']);
});

it('should validate asset_id exists', function () {
    $response = $this->postJson(route('admin.dam.assets.tag'), [
        'tag'      => 'some-tag',
        'asset_id' => 99999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['asset_id']);
});
