<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Models\Product;

/**
 * Builds a product carrying an asset attribute, written the way the importer writes
 * it — straight to the `values` column, without going through ProductRepository.
 */
function damImportedProductFixture(string $scope = 'common', bool $scoped = false): array
{
    $assetCode = 'asset_'.Str::random(8);

    $assetAttribute = Attribute::factory()->create([
        'code'              => $assetCode,
        'type'              => 'asset',
        'value_per_locale'  => (int) $scoped,
        'value_per_channel' => (int) $scoped,
    ]);

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily(
        $family,
        Attribute::whereIn('code', [$assetCode])->get()
    );

    $asset = Asset::factory()->create();

    $values = match ($scope) {
        'channel_locale_specific' => ['channel_locale_specific' => ['default' => ['en_US' => [$assetCode => [$asset->id]]]]],
        'locale_specific'         => ['locale_specific' => ['en_US' => [$assetCode => [$asset->id]]]],
        'channel_specific'        => ['channel_specific' => ['default' => [$assetCode => [$asset->id]]]],
        default                   => ['common' => [$assetCode => [$asset->id]]],
    };

    $product = Product::create([
        'sku'                 => 'IMP-'.Str::random(8),
        'attribute_family_id' => $family->id,
    ]);

    DB::table('products')->where('id', $product->id)->update(['values' => json_encode($values)]);

    return ['product' => $product->fresh(), 'asset' => $asset, 'assetCode' => $assetCode];
}

it('links an imported asset to its product for every value scope', function (string $scope, bool $scoped) {
    $fixture = damImportedProductFixture($scope, $scoped);

    expect(DB::table('dam_asset_resource_mappings')->where('product_id', $fixture['product']->id)->count())
        ->toBe(0);

    Event::dispatch('data_transfer.imports.batch.product.created.after', [
        'product_id' => [$fixture['product']->id],
    ]);

    $mapping = DB::table('dam_asset_resource_mappings')
        ->where('product_id', $fixture['product']->id)
        ->first();

    expect($mapping)->not->toBeNull()
        ->and((int) $mapping->dam_asset_id)->toBe($fixture['asset']->id)
        ->and($mapping->related_field)->toBe($fixture['assetCode']);
})->with([
    'common'                  => ['common', false],
    'locale specific'         => ['locale_specific', true],
    'channel specific'        => ['channel_specific', true],
    'channel locale specific' => ['channel_locale_specific', true],
]);

it('links imported assets on the updated-after event too', function () {
    $fixture = damImportedProductFixture();

    Event::dispatch('data_transfer.imports.batch.product.updated.after', [
        'product_id' => [$fixture['product']->id],
    ]);

    expect(DB::table('dam_asset_resource_mappings')->where('product_id', $fixture['product']->id)->count())
        ->toBe(1);
});

it('is idempotent across repeated import batches', function () {
    $fixture = damImportedProductFixture();

    Event::dispatch('data_transfer.imports.batch.product.created.after', ['product_id' => [$fixture['product']->id]]);
    Event::dispatch('data_transfer.imports.batch.product.updated.after', ['product_id' => [$fixture['product']->id]]);

    expect(DB::table('dam_asset_resource_mappings')->where('product_id', $fixture['product']->id)->count())
        ->toBe(1);
});

it('ignores an empty batch payload', function () {
    Event::dispatch('data_transfer.imports.batch.product.created.after', ['product_id' => []]);

    expect(DB::table('dam_asset_resource_mappings')->count())->toBe(0);
});

/**
 * The DAM product importer stores resolved ids the way
 * `Webkul\DAM\Helpers\Importers\Product\Importer::prepareAttributeValues()` writes them —
 * `implode(',', $assets)`, a comma-separated string rather than an array.
 */
it('links assets stored as a comma-separated string, as the importer writes them', function () {
    $assetCode = 'asset_'.Str::random(8);

    Attribute::factory()->create([
        'code'              => $assetCode,
        'type'              => 'asset',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [$assetCode])->get());

    $first = Asset::factory()->create();
    $second = Asset::factory()->create();

    $product = Product::create([
        'sku'                 => 'IMP-'.Str::random(8),
        'attribute_family_id' => $family->id,
    ]);

    DB::table('products')->where('id', $product->id)->update([
        'values' => json_encode(['common' => [$assetCode => $first->id.','.$second->id]]),
    ]);

    Event::dispatch('data_transfer.imports.batch.product.created.after', ['product_id' => [$product->id]]);

    $mapped = DB::table('dam_asset_resource_mappings')
        ->where('product_id', $product->id)
        ->pluck('dam_asset_id')
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($mapped)->toBe(collect([$first->id, $second->id])->sort()->values()->all());
});
