<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

uses(DatabaseTransactions::class);

/*
 * An asset attribute placed at the common level belongs to the configurable. Its
 * variants render it locked, read it through the ancestor chain, and must never
 * store a copy of it — a stored copy wins the merge and the parent would stop
 * being the source of truth. Placed at the variant level the same attribute stays
 * the variant's own, and every other attribute type keeps working as it did.
 */

function damInheritanceFixture(
    string $assetPlacement = 'common',
    int $assetCount = 2,
    int $childCount = 1,
    bool $assetScoped = false
): array {
    $axisCode = 'axis_'.Str::random(8);
    $assetCode = 'asset_'.Str::random(8);
    $textCode = 'text_'.Str::random(8);

    $axis = Attribute::factory()->create(['code' => $axisCode, 'type' => 'select']);

    $assetAttribute = Attribute::factory()->create([
        'code'              => $assetCode,
        'type'              => 'asset',
        'value_per_locale'  => (int) $assetScoped,
        'value_per_channel' => (int) $assetScoped,
    ]);

    Attribute::factory()->create([
        'code'              => $textCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $axisCode,
        $assetCode,
        $textCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $axis->id, 'level' => 'level_1', 'position' => 0],
    ]);

    if ($assetPlacement !== 'common') {
        VariantStructureAttribute::create([
            'variant_structure_id' => $structure->id,
            'attribute_id'         => $assetAttribute->id,
            'level'                => $assetPlacement,
        ]);
    }

    $assets = Asset::factory()->createMany(max($assetCount, 1));

    $assetIds = $assetCount > 0 ? $assets->pluck('id')->all() : [];

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'CFG-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$axisCode],
    ]);

    $common = [
        'sku'     => $configurable->sku,
        $textCode => 'PARENT-TEXT',
    ];

    $values = [];

    if ($assetIds !== []) {
        if ($assetScoped) {
            $values[AbstractType::CHANNEL_LOCALE_VALUES_KEY] = [
                core()->getRequestedChannelCode() => [
                    core()->getRequestedLocaleCode() => [$assetCode => $assetIds],
                ],
            ];
        } else {
            $common[$assetCode] = $assetIds;
        }
    }

    $values[AbstractType::COMMON_VALUES_KEY] = $common;

    $configurable->values = $values;

    $configurable->save();

    $children = [];

    for ($position = 1; $position <= $childCount; $position++) {
        $option = $axis->options->get($position - 1) ?? $axis->options->first();

        $variant = $configurable->getTypeInstance()->createVariant($configurable, $configurable->super_attributes, [
            'parent_id' => $configurable->id,
            'sku'       => $configurable->sku.'-v'.$position,
            'values'    => ['common' => [$axisCode => $option->code]],
        ]);

        $children[] = Product::find($variant->id);
    }

    return [
        'configurable' => $configurable->fresh(),
        'child'        => $children[0],
        'children'     => $children,
        'assets'       => $assets,
        'assetIds'     => $assetIds,
        'axisCode'     => $axisCode,
        'assetCode'    => $assetCode,
        'textCode'     => $textCode,
    ];
}

function damSubmitProductValues(Product $product, array $common): TestResponse
{
    $existing = $product->values['common'] ?? [];

    return test()->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => 1,
        'values' => [
            'common' => array_merge($existing, ['sku' => $product->sku], $common),
        ],
    ]);
}

function damStoredCommonValues(Product $product): array
{
    $values = json_decode(DB::table('products')->where('id', $product->id)->value('values'), true);

    return $values['common'] ?? [];
}

describe('a common asset attribute on a variant', function () {
    it('renders the parent assets on the child as a locked control', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('asset-values="'.implode(',', $fixture['assetIds']).'"')
            ->and($content)->toContain(':readonly="true"');
    });

    it('leaves the parent its own control unlocked', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['configurable']->id))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('asset-values="'.implode(',', $fixture['assetIds']).'"')
            ->and($content)->not->toContain(':readonly="true"');
    });

    it('resolves the parent assets through the ancestor chain', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        expect($fixture['child']->resolvedValues()['common'][$fixture['assetCode']])->toBe($fixture['assetIds'])
            ->and(damStoredCommonValues($fixture['child']))->not->toHaveKey($fixture['assetCode']);
    });

    it('refuses to store assets submitted on the child', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        $intruder = Asset::factory()->create();

        damSubmitProductValues($fixture['child'], [$fixture['assetCode'] => [$intruder->id]])
            ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

        $child = Product::find($fixture['child']->id);

        expect(damStoredCommonValues($child))->not->toHaveKey($fixture['assetCode'])
            ->and($child->resolvedValues()['common'][$fixture['assetCode']])->toBe($fixture['assetIds']);
    });

    it('refuses to clear the inherited assets from the child', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        damSubmitProductValues($fixture['child'], [$fixture['assetCode'] => []]);

        $child = Product::find($fixture['child']->id);

        expect(damStoredCommonValues($child))->not->toHaveKey($fixture['assetCode'])
            ->and($child->resolvedValues()['common'][$fixture['assetCode']])->toBe($fixture['assetIds']);
    });

    it('refuses assets submitted on the child in a channel and locale scoped bucket', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture(assetScoped: true);

        $channelCode = core()->getRequestedChannelCode();
        $localeCode = core()->getRequestedLocaleCode();

        $intruder = Asset::factory()->create();

        $this->put(route('admin.catalog.products.update', $fixture['child']->id), [
            'sku'    => $fixture['child']->sku,
            'status' => 1,
            'values' => [
                'common'                  => array_merge($fixture['child']->values['common'] ?? [], ['sku' => $fixture['child']->sku]),
                'channel_locale_specific' => [
                    $channelCode => [
                        $localeCode => [$fixture['assetCode'] => [$intruder->id]],
                    ],
                ],
            ],
        ]);

        $stored = json_decode(DB::table('products')->where('id', $fixture['child']->id)->value('values'), true);

        $resolved = Product::find($fixture['child']->id)->resolvedValues();

        expect(data_get($stored, "channel_locale_specific.{$channelCode}.{$localeCode}.".$fixture['assetCode']))->toBeNull()
            ->and(data_get($resolved, "channel_locale_specific.{$channelCode}.{$localeCode}.".$fixture['assetCode']))
            ->toBe($fixture['assetIds']);
    });
});

describe('the parent staying the source of truth', function () {
    it('shows the parent assets after they change', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        $replacement = Asset::factory()->create();

        $configurable = $fixture['configurable'];

        $configurable->values = array_replace_recursive($configurable->values, [
            'common' => [$fixture['assetCode'] => [$fixture['assetIds'][0], $replacement->id]],
        ]);

        $configurable->save();

        expect(Product::find($fixture['child']->id)->resolvedValues()['common'][$fixture['assetCode']])
            ->toBe([$fixture['assetIds'][0], $replacement->id]);
    });

    it('reaches every child of the parent', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture(childCount: 2);

        foreach ($fixture['children'] as $child) {
            expect($child->resolvedValues()['common'][$fixture['assetCode']])->toBe($fixture['assetIds'])
                ->and(damStoredCommonValues($child))->not->toHaveKey($fixture['assetCode']);
        }
    });

    it('keeps the child locked when the parent holds no assets', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture(assetCount: 0);

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('asset-values=""')
            ->and($content)->toContain(':readonly="true"');
    });
});

describe('attributes the variant owns', function () {
    it('stores an asset attribute placed at the variant level', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture(assetPlacement: 'variant');

        $own = Asset::factory()->create();

        damSubmitProductValues($fixture['child'], [$fixture['assetCode'] => [$own->id]])
            ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

        expect(damStoredCommonValues(Product::find($fixture['child']->id))[$fixture['assetCode']])
            ->toBe((string) $own->id);
    });

    it('renders a variant level asset control unlocked on the child', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture(assetPlacement: 'variant');

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect($content)->not->toContain(':readonly="true"');
    });

    it('leaves other attribute types on the child exactly as they were', function () {
        $this->loginAsAdmin();

        $fixture = damInheritanceFixture();

        damSubmitProductValues($fixture['child'], [$fixture['textCode'] => 'CHILD-TEXT']);

        expect(damStoredCommonValues(Product::find($fixture['child']->id))[$fixture['textCode']])->toBe('CHILD-TEXT');
    });

    it('stores assets on a product outside any variant structure', function () {
        $this->loginAsAdmin();

        $assetAttribute = Attribute::factory()->create(['type' => 'asset']);

        $family = AttributeFamily::factory()->create();

        AttributeFamily::factory()->linkAttributeGroupToFamily($family);
        AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [$assetAttribute->code])->get());

        $product = Product::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => []],
        ]);

        $asset = Asset::factory()->create();

        damSubmitProductValues($product, [$assetAttribute->code => [$asset->id]]);

        expect(damStoredCommonValues(Product::find($product->id))[$assetAttribute->code])->toBe((string) $asset->id);
    });
});
