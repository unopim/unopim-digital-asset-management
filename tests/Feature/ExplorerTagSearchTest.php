<?php

declare(strict_types=1);

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;

beforeEach(fn () => $this->loginAsAdmin());

it('finds an asset by its tag name via the explorer global search', function () {
    $dir = Directory::factory()->create(['name' => 'Campaign', 'parent_id' => null]);

    // Tagged asset whose file name does NOT contain the search term — so a hit can
    // only come from the tag, proving the search reaches the tag relationship.
    $tagged = Asset::factory()->create(['file_name' => 'IMG_001.jpg']);
    $tagged->directories()->attach($dir->id);
    $tagged->tags()->attach(Tag::create(['name' => 'sunset'])->id);

    $untagged = Asset::factory()->create(['file_name' => 'IMG_002.jpg']);
    $untagged->directories()->attach($dir->id);

    $response = $this->getJson(route('admin.dam.explorer.index', [
        'directory_id' => $dir->id,
        'search'       => 'sunset',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['file_name' => 'IMG_001.jpg']);
    $response->assertJsonMissing(['file_name' => 'IMG_002.jpg']);
    expect($response->json('meta.total_assets'))->toBe(1);
});

it('matches tags case-insensitively and on partial terms', function () {
    $dir = Directory::factory()->create(['name' => 'Campaign', 'parent_id' => null]);

    $asset = Asset::factory()->create(['file_name' => 'IMG_010.jpg']);
    $asset->directories()->attach($dir->id);
    $asset->tags()->attach(Tag::create(['name' => 'Summer Sale'])->id);

    $response = $this->getJson(route('admin.dam.explorer.index', [
        'directory_id' => $dir->id,
        'search'       => 'summer',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['file_name' => 'IMG_010.jpg']);
    expect($response->json('meta.total_assets'))->toBe(1);
});

it('still finds an asset by its file name (regression)', function () {
    $dir = Directory::factory()->create(['name' => 'Campaign', 'parent_id' => null]);

    $asset = Asset::factory()->create(['file_name' => 'beach-holiday.jpg']);
    $asset->directories()->attach($dir->id);

    $response = $this->getJson(route('admin.dam.explorer.index', [
        'directory_id' => $dir->id,
        'search'       => 'beach',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['file_name' => 'beach-holiday.jpg']);
    expect($response->json('meta.total_assets'))->toBe(1);
});

it('returns each asset once even when multiple of its tags match the term', function () {
    $dir = Directory::factory()->create(['name' => 'Campaign', 'parent_id' => null]);

    $asset = Asset::factory()->create(['file_name' => 'IMG_020.jpg']);
    $asset->directories()->attach($dir->id);
    $asset->tags()->attach(Tag::create(['name' => 'travel'])->id);
    $asset->tags()->attach(Tag::create(['name' => 'travel-blog'])->id);

    $response = $this->getJson(route('admin.dam.explorer.index', [
        'directory_id' => $dir->id,
        'search'       => 'travel',
    ]));

    $response->assertOk();
    expect($response->json('meta.total_assets'))->toBe(1);
    expect($response->json('assets'))->toHaveCount(1);
});
