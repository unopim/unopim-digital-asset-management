<?php

use Illuminate\Support\Str;
use Webkul\Category\Models\CategoryField;
use Webkul\DAM\Helpers\Exporters\Category\Exporter;
use Webkul\DAM\Models\Asset;

/*
 * The importer's "blank cell unlinks" fix (Importer::resolveAssetFieldValue) stores an
 * empty string for an unlinked asset field. The exporter never learned about that
 * value: it fed it straight into `findWhereIn('id', [''])`, which PostgreSQL rejects
 * outright (`invalid input syntax for type bigint: ""`), breaking every export batch
 * that reaches an unlinked category.
 */

function damCategoryExporter(): Exporter
{
    return app(Exporter::class);
}

function damCallExporterSetFields(Exporter $exporter, array $additionalData): array
{
    (new ReflectionProperty($exporter, 'filters'))->setValue($exporter, ['with_media' => false]);

    return (new ReflectionMethod($exporter, 'setFieldsAdditionalData'))
        ->invoke($exporter, $additionalData, null, []);
}

beforeEach(function (): void {
    $this->damResetAssetTables();

    $this->assetFieldCode = 'cat_asset_'.Str::random(6);

    $this->assetField = CategoryField::factory()->create([
        'code'             => $this->assetFieldCode,
        'type'             => 'asset',
        'status'           => 1,
        'value_per_locale' => 0,
    ]);

    $this->asset = Asset::factory()->create(['path' => 'assets/Root/'.Str::random(6).'.jpg']);

    $this->exporter = damCategoryExporter();

    (new ReflectionProperty($this->exporter, 'categoryFields'))->setValue($this->exporter, [$this->assetField]);
});

it('exports an unlinked asset field as an empty value instead of querying an empty id', function () {
    $fieldValues = damCallExporterSetFields($this->exporter, [$this->assetFieldCode => '']);

    expect($fieldValues[$this->assetFieldCode])->toBe('');
});

it('still resolves a linked asset field to its stored path', function () {
    $fieldValues = damCallExporterSetFields($this->exporter, [$this->assetFieldCode => (string) $this->asset->id]);

    expect($fieldValues[$this->assetFieldCode])->toBe($this->asset->path);
});
