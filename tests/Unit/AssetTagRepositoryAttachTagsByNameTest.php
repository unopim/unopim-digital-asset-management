<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Repositories\AssetTagRepository;

it('creates missing tags and attaches both new and existing ones', function () {
    $asset = Asset::factory()->create();
    $existing = Tag::create(['name' => 'ocean']);

    app(AssetTagRepository::class)->attachTagsByName($asset, ['ocean', 'sunset']);

    $names = $asset->refresh()->tags->pluck('name')->sort()->values()->all();

    expect($names)->toBe(['ocean', 'sunset']);
    expect(Tag::whereRaw('LOWER(name) = ?', ['ocean'])->count())->toBe(1);
});

it('is idempotent when the same names are attached again', function () {
    $asset = Asset::factory()->create();
    $repository = app(AssetTagRepository::class);

    $repository->attachTagsByName($asset, ['forest', 'lake']);
    $repository->attachTagsByName($asset, ['forest', 'lake']);

    expect($asset->refresh()->tags)->toHaveCount(2);
});

it('reuses an existing tag matched case-insensitively', function () {
    $asset = Asset::factory()->create();
    Tag::create(['name' => 'Mountain']);

    app(AssetTagRepository::class)->attachTagsByName($asset, ['mountain']);

    expect(Tag::whereRaw('LOWER(name) = ?', ['mountain'])->count())->toBe(1);
    expect($asset->refresh()->tags->pluck('name')->all())->toBe(['Mountain']);
});

it('looks up existing tags in a single batch query, not one per tag', function () {
    $asset = Asset::factory()->create();
    Tag::create(['name' => 'alpha']);
    Tag::create(['name' => 'beta']);

    DB::enableQueryLog();

    app(AssetTagRepository::class)->attachTagsByName($asset, ['alpha', 'beta', 'gamma']);

    $selects = collect(DB::getQueryLog())->filter(fn ($q) => str_contains(strtolower($q['query']), 'lower(name)'));

    DB::disableQueryLog();

    expect($selects)->toHaveCount(1);
});
