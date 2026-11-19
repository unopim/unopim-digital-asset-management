<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\DAM\Contracts\Adapters\CategoryResolver;
use Webkul\DAM\Contracts\Adapters\ProductResolver;
use Webkul\DAM\Integrations\Pim\PimCategoryResolver;
use Webkul\DAM\Integrations\Pim\PimProductResolver;
use Webkul\DAM\Integrations\Standalone\NullCategoryResolver;
use Webkul\DAM\Integrations\Standalone\NullProductResolver;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetResourceMapping;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\DAM\Providers\EventServiceProvider;
use Webkul\DAM\Support\PimIntegration;

uses(DatabaseTransactions::class);

function rebindResolvers(): void
{
    app()->forgetInstance(ProductResolver::class);
    app()->forgetInstance(CategoryResolver::class);
}

beforeEach(function () {
    PimIntegration::clearFake();
});

afterEach(function () {
    PimIntegration::clearFake();
    rebindResolvers();
});

it('is enabled by default when the catalog core is present', function () {
    PimIntegration::clearFake();

    expect(PimIntegration::corePresent())->toBeTrue()
        ->and(PimIntegration::enabled())->toBeTrue();
});

it('ignores any stored dam_configuration row — there is no runtime toggle', function () {
    DamConfiguration::updateOrCreate(['key' => 'DAM_PIM_ENABLED'], ['value' => '0']);

    PimIntegration::clearFake();

    expect(PimIntegration::enabled())->toBeTrue();

    DamConfiguration::where('key', 'DAM_PIM_ENABLED')->delete();
});

it('binds the pim resolvers when pim is enabled', function () {
    PimIntegration::fake(true);
    rebindResolvers();

    expect(app(ProductResolver::class))->toBeInstanceOf(PimProductResolver::class)
        ->and(app(CategoryResolver::class))->toBeInstanceOf(PimCategoryResolver::class)
        ->and(app(ProductResolver::class)->isAvailable())->toBeTrue();
});

it('binds the null resolvers when pim is disabled', function () {
    PimIntegration::fake(false);
    rebindResolvers();

    expect(app(ProductResolver::class))->toBeInstanceOf(NullProductResolver::class)
        ->and(app(CategoryResolver::class))->toBeInstanceOf(NullCategoryResolver::class)
        ->and(app(ProductResolver::class)->isAvailable())->toBeFalse()
        ->and(app(ProductResolver::class)->modelClass())->toBeNull()
        ->and(app(CategoryResolver::class)->modelClass())->toBeNull();
});

it('registers the catalog event listeners only when pim is enabled', function () {
    $provider = new EventServiceProvider(app());

    PimIntegration::fake(true);
    expect($provider->listens())->toHaveKey('catalog.category.create.after')
        ->and($provider->listens())->toHaveKey('catalog.product.create.after');

    PimIntegration::fake(false);
    expect($provider->listens())->toBe([]);
});

it('resolves asset mapping relations without a fatal when pim is off', function () {
    PimIntegration::fake(false);
    rebindResolvers();

    $asset = Asset::factory()->create();

    $mapping = AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($mapping->product()->getRelated())->toBeInstanceOf(AssetResourceMapping::class)
        ->and($mapping->product)->toBeNull()
        ->and($mapping->category)->toBeNull();
});
