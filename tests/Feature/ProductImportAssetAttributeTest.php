<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreImporter;
use Webkul\DataTransfer\Models\JobTrack;

uses(DatabaseTransactions::class);

function damImportFixture(): array
{
    $assetCode = 'asset_'.Str::random(8);
    $textCode = 'text_'.Str::random(8);

    Attribute::factory()->create(['code' => $assetCode, 'type' => Asset::ASSET_ATTRIBUTE_TYPE]);
    Attribute::factory()->create(['code' => $textCode, 'type' => 'text']);

    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', ['sku', 'status', $assetCode, $textCode])->get());

    $path = 'assets/Root/Seed/'.Str::random(6).'/sheet.pdf';

    $asset = Asset::create([
        'file_name' => 'sheet.pdf',
        'file_type' => 'document',
        'file_size' => 10,
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'path'      => $path,
    ]);

    $errors = new Error;

    $importer = app(CoreImporter::class)
        ->setImport(JobTrack::factory()->create())
        ->setErrorHelper($errors);

    return [$family->fresh(), $assetCode, $textCode, $asset, $importer, $errors];
}

it('stores the asset id rather than the path carried by the row', function () {
    [$family, $assetCode, $textCode, $asset, $importer] = damImportFixture();

    $attributeValues = [];

    $importer->prepareAttributeValues([
        'sku'              => 'dam-import-'.uniqid(),
        'type'             => 'simple',
        'attribute_family' => $family->code,
        $assetCode         => $asset->path,
        $textCode          => "'=formula'",
    ], $attributeValues);

    expect($attributeValues['common'][$assetCode] ?? null)->toBe((string) $asset->id)
        ->and($attributeValues['common'][$textCode] ?? null)->toBe('=formula');
});

it('leaves the value unset and reports a path that resolves to no asset', function () {
    [$family, $assetCode, , , $importer, $errors] = damImportFixture();

    $attributeValues = [];

    $importer->prepareAttributeValues([
        'sku'              => 'dam-import-'.uniqid(),
        'type'             => 'simple',
        'attribute_family' => $family->code,
        $assetCode         => 'assets/Root/missing.pdf',
    ], $attributeValues);

    expect($attributeValues['common'] ?? [])->not->toHaveKey($assetCode)
        ->and($errors->getAllErrorsGroupedByCode())
        ->toHaveKey($importer::ERROR_CODE_ASSET_NOT_FOUND);
});
