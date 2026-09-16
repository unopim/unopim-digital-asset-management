<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Category\Models\CategoryField;
use Webkul\DAM\Helpers\Importers\Category\Importer;
use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Models\JobTrack;

/*
 * A blank asset column is an unlink instruction, and the importer owns the mappings the
 * category importer's bulk upsert never lets an event reach. Both failures left an asset
 * reporting resources it was no longer attached to, which blocks deleting it for good.
 */

function damCategoryImporter(): Importer
{
    $importer = app(Importer::class)->setErrorHelper(app(Error::class));

    return $importer->setImport(new JobTrack(['images_directory_path' => '']));
}

function damCallImporter(Importer $importer, string $method, array $arguments): mixed
{
    return (new ReflectionMethod($importer, $method))->invokeArgs($importer, $arguments);
}

function damImporterStorage(Importer $importer): Storage
{
    return (new ReflectionProperty($importer, 'categoryStorage'))->getValue($importer);
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

    $this->importer = damCategoryImporter();
});

it('resolves a blank asset column to an empty value so the field is unlinked', function () {
    expect(damCallImporter($this->importer, 'resolveAssetFieldValue', ['', (string) $this->asset->id]))
        ->toBe('');
});

it('resolves an asset path to its id', function () {
    expect(damCallImporter($this->importer, 'resolveAssetFieldValue', [$this->asset->path, '']))
        ->toBe((string) $this->asset->id);
});

it('keeps the current value when a non-empty column resolves to nothing', function () {
    expect(damCallImporter($this->importer, 'resolveAssetFieldValue', ['assets/Root/missing.jpg', (string) $this->asset->id]))
        ->toBe((string) $this->asset->id);
});

it('creates the linked resource for an imported category', function () {
    $categoryId = DB::table('categories')->insertGetId([
        'code'            => 'imported_'.Str::random(6),
        'additional_data' => json_encode(['common' => [$this->assetFieldCode => (string) $this->asset->id]]),
        '_lft'            => 1,
        '_rgt'            => 2,
    ]);

    damImporterStorage($this->importer)->set('imported', $categoryId);

    damCallImporter($this->importer, 'syncAssetMappings', [[
        'update' => [
            'imported' => ['additional_data' => ['common' => [$this->assetFieldCode => (string) $this->asset->id]]],
        ],
    ]]);

    $mapping = DB::table('dam_asset_resource_mappings')->where('category_id', $categoryId)->first();

    expect($mapping)->not->toBeNull()
        ->and((int) $mapping->dam_asset_id)->toBe($this->asset->id)
        ->and($mapping->related_field)->toBe($this->assetFieldCode);
});

it('removes the linked resource when the imported category clears the field', function () {
    $categoryId = DB::table('categories')->insertGetId([
        'code'            => 'cleared_'.Str::random(6),
        'additional_data' => json_encode(['common' => [$this->assetFieldCode => '']]),
        '_lft'            => 1,
        '_rgt'            => 2,
    ]);

    DB::table('dam_asset_resource_mappings')->insert([
        'type'          => 'category',
        'dam_asset_id'  => $this->asset->id,
        'category_id'   => $categoryId,
        'related_field' => $this->assetFieldCode,
    ]);

    damImporterStorage($this->importer)->set('cleared', $categoryId);

    damCallImporter($this->importer, 'syncAssetMappings', [[
        'update' => [
            'cleared' => ['additional_data' => ['common' => [$this->assetFieldCode => '']]],
        ],
    ]]);

    expect(DB::table('dam_asset_resource_mappings')->where('category_id', $categoryId)->count())->toBe(0);
});

it('purges the category mappings of a deleted field and keeps product mappings', function () {
    DB::table('dam_asset_resource_mappings')->insert([
        'type'          => 'category',
        'dam_asset_id'  => $this->asset->id,
        'category_id'   => 1,
        'related_field' => 'a_field_that_no_longer_exists',
    ]);

    DB::table('dam_asset_resource_mappings')->insert([
        'type'          => 'product',
        'dam_asset_id'  => $this->asset->id,
        'related_field' => 'a_field_that_no_longer_exists',
    ]);

    Event::dispatch('catalog.category_field.delete.after', $this->assetField->id);

    expect(DB::table('dam_asset_resource_mappings')->where('type', 'category')->count())->toBe(0)
        ->and(DB::table('dam_asset_resource_mappings')->where('type', 'product')->count())->toBe(1);
});
