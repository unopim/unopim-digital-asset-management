<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\AdminApi\ApiDataSource\Catalog\SimpleProductDataSource as BaseSimpleProductDataSource;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DAM\ApiDataSource\Catalog\SimpleProductDataSource as DamSimpleProductDataSource;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\PublicUrl\Providers\PublicUrlServiceProvider;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Create a simple product whose family carries one `asset` attribute, place the
 * given asset ids into its common scope, and return the product with metadata.
 *
 * @param  array<int, int>  $assetIds
 */
function productWithAssetAttribute(array $assetIds): array
{
    $assetCode = 'asset_'.Str::random(8);

    $assetAttribute = Attribute::factory()->create([
        'code'              => $assetCode,
        'type'              => 'asset',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily(
        $family,
        Attribute::whereIn('code', [$assetCode])->get()
    );

    $product = app(ProductRepository::class)->create([
        'type'                => 'simple',
        'attribute_family_id' => $family->id,
        'sku'                 => 'SKU-'.Str::random(8),
    ]);

    $product->values = [
        AbstractType::COMMON_VALUES_KEY => [
            'sku'      => $product->sku,
            $assetCode => $assetIds,
        ],
    ];

    $product->save();

    return [
        'sku'       => $product->sku,
        'assetCode' => $assetCode,
    ];
}

it('resolves the DAM data source over the base one when PublicUrl is absent', function () {
    expect(class_exists(PublicUrlServiceProvider::class))
        ->toBeFalse('PublicUrl must not be installed for this expectation');

    expect(app(BaseSimpleProductDataSource::class))->toBeInstanceOf(DamSimpleProductDataSource::class);
});

it('returns the asset path, not the asset id, for an asset attribute in the product GET API', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/Product Photography/red-shoe.jpg']);

    $fixture = productWithAssetAttribute([$asset->id]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $fixture['sku']]))
        ->assertOk();

    $value = $response->json('values.common.'.$fixture['assetCode']);

    expect($value)->toBe($asset->path)
        ->and($value)->not->toBe((string) $asset->id);
});

it('joins multiple asset paths with a comma for a multi-asset attribute value', function () {
    $first = Asset::factory()->create(['path' => 'assets/Root/a.jpg']);
    $second = Asset::factory()->create(['path' => 'assets/Root/b.jpg']);

    $fixture = productWithAssetAttribute([$first->id, $second->id]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $fixture['sku']]))
        ->assertOk();

    expect($response->json('values.common.'.$fixture['assetCode']))
        ->toBe($first->path.', '.$second->path);
});

it('keeps the original value when the referenced asset no longer exists', function () {
    $fixture = productWithAssetAttribute([999999]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $fixture['sku']]))
        ->assertOk();

    expect($response->json('values.common.'.$fixture['assetCode']))->toBe('999999');
});
