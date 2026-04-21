<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;

it('can create a comment using factory', function () {
    $comment = AssetComments::factory()->create();

    expect($comment)->toBeInstanceOf(AssetComments::class);
    expect($comment->comments)->toBeString();
    expect($comment->dam_asset_id)->toBeInt();
});

it('has correct table name', function () {
    $comment = new AssetComments;
    expect($comment->getTable())->toBe('dam_asset_comments');
});

it('has correct fillable attributes', function () {
    $comment = new AssetComments;
    expect($comment->getFillable())->toBe(['admin_id', 'parent_id', 'comments', 'dam_asset_id']);
});

it('belongs to an asset', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);

    expect($comment->asset->id)->toBe($asset->id);
});

it('can have child replies', function () {
    $asset = Asset::factory()->create();
    $parent = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => null,
    ]);
    $child1 = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => $parent->id,
    ]);
    $child2 = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(2);
    expect($parent->children->pluck('id')->toArray())->toContain($child1->id, $child2->id);
});

it('returns correct primary model id for history', function () {
    $asset = Asset::factory()->create();
    $comment = AssetComments::factory()->create(['dam_asset_id' => $asset->id]);

    expect($comment->getPrimaryModelIdForHistory())->toBe($asset->id);
});

it('has correct audit exclude columns', function () {
    $comment = new AssetComments;
    $auditExclude = (new ReflectionProperty($comment, 'auditExclude'))->getValue($comment);

    expect($auditExclude)->toContain('id', 'parent_id', 'dam_asset_id');
});
