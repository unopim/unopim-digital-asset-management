<?php

use Illuminate\Support\Collection;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\DAM\Helpers\Normalizers\ProductValuesNormalizer;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

beforeEach(function () {
    $attributes = collect([
        'images' => 'gallery',
        'weight' => 'measurement',
        'name'   => 'text',
        'price'  => 'price',
        'assets' => 'asset',
    ])->map(fn ($type, $code) => new Attribute(['code' => $code, 'type' => $type]));

    $service = Mockery::mock(AttributeService::class);
    $service->shouldReceive('findAttributeByCode')
        ->andReturnUsing(fn ($code) => $attributes->get($code));

    $this->assets = Mockery::mock(AssetRepository::class);
    $this->normalizer = new ProductValuesNormalizer($service, $this->assets);
    $this->coreNormalizer = new ProductAttributeValuesNormalizer($service);
});

it('preserves core gallery formatting', function () {
    $values = ['images' => ['products/front.jpg', 'products/back.jpg']];
    $options = ['locale' => 'en_US', 'forExport' => true];

    expect($this->normalizer->normalizeAttributes($values, $options))
        ->toBe($this->coreNormalizer->normalizeAttributes($values, $options))
        ->toBe(['images' => 'products/front.jpg, products/back.jpg']);
});

it('preserves core measurement export expansion', function ($value) {
    $repository = Mockery::mock(AttributeMeasurementRepository::class);
    $repository->shouldReceive('getByAttributeId')->andReturn(null);
    app()->instance(MeasurementHelper::class, new MeasurementHelper($repository));

    $values = ['weight' => $value];
    $options = ['locale' => 'en_US', 'forExport' => true];

    expect($this->normalizer->normalizeAttributes($values, $options))
        ->toBe($this->coreNormalizer->normalizeAttributes($values, $options))
        ->toBe(['weight' => '12.5', 'weight(unit)' => 'KILOGRAM']);
})->with([
    'array'  => [['amount' => '12.5', 'unit' => 'KILOGRAM']],
    'json'   => ['{"amount":"12.5","unit":"KILOGRAM"}'],
    'scoped' => [['<all_channels>' => ['<all_locales>' => ['amount' => '12.5', 'unit' => 'KILOGRAM']]]],
]);

it('preserves core ordinary text normalization', function ($value, $expected) {
    $values = ['name' => $value];
    $options = ['locale' => 'en_US', 'forExport' => true];

    expect($this->normalizer->normalizeAttributes($values, $options))
        ->toBe($this->coreNormalizer->normalizeAttributes($values, $options))
        ->toBe(['name' => $expected]);
})->with([
    'plain text'         => ['Product name', 'Product name'],
    'equals'             => ['=1+2', "'=1+2'"],
    'plus'               => ['+value', "'+value'"],
    'minus'              => ['-value', "'-value'"],
    'at sign'            => ['@value', "'@value'"],
    'leading whitespace' => [" \t=1+2", "' \t=1+2'"],
    'null'               => [null, null],
    'numeric'            => [12.5, 12.5],
]);

it('preserves core price normalization', function ($value, $forExport, $expected) {
    $values = ['price' => $value];
    $options = ['locale' => 'en_US', 'forExport' => $forExport];

    expect($this->normalizer->normalizeAttributes($values, $options))
        ->toBe($this->coreNormalizer->normalizeAttributes($values, $options))
        ->toBe($expected);
})->with([
    'export currencies'    => [['USD' => '12.50', 'EUR' => 0], true, ['price (USD)' => '12.50', 'price (EUR)' => 0]],
    'string export option' => [['USD' => '-12.50'], 'true', ['price (USD)' => '-12.50']],
    'invalid export value' => ['12.50', true, []],
    'empty export value'   => [[], true, []],
    'non-export'           => [['USD' => '12.50'], false, ['price' => ['USD' => '12.50']]],
]);

it('keeps asset paths in selected order while ignoring unknown asset ids', function ($ids) {
    $this->assets->shouldReceive('findWhereIn')->with('id', ['22', '999', '11', '22'])
        ->once()->andReturn(new Collection([
            (object) ['id' => 11, 'path' => 'assets/first.jpg'],
            (object) ['id' => 22, 'path' => 'assets/second.jpg'],
        ]));

    expect($this->normalizer->normalizeAttributes(['assets' => $ids], ['locale' => 'en_US', 'forExport' => true]))
        ->toBe(['assets' => 'assets/second.jpg, assets/first.jpg, assets/second.jpg']);
})->with([
    'array'           => [['22', '999', '11', '22']],
    'comma separated' => ['22, 999, 11, 22'],
]);

it('ignores unknown attributes as the core normalizer does', function () {
    $values = ['missing' => 'value'];
    $options = ['locale' => 'en_US'];

    expect($this->normalizer->normalizeAttributes($values, $options))
        ->toBe($this->coreNormalizer->normalizeAttributes($values, $options))
        ->toBe([]);
});
