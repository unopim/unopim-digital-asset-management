<?php

use Webkul\DAM\Helpers\Importers\Product\Concerns\ImportsAssetAttributes;
use Webkul\DAM\Helpers\Importers\Product\MeasurementAwareImporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreImporter;
use Webkul\Measurement\Helpers\Importers\Product\Importer as MeasurementImporter;

/*
 * Measurement binds the core product importer inside register(); a plain $bindings
 * entry on the DAM provider loses that race and the asset branch never runs.
 */

it('resolves the product importer to one that imports asset attributes', function () {
    $importer = app(config('importers.products.importer'));

    expect(class_uses_recursive($importer))->toContain(ImportsAssetAttributes::class);
});

it('keeps the measurement importer behaviour when that package is installed', function () {
    $importer = app(CoreImporter::class);

    expect($importer)->toBeInstanceOf(MeasurementImporter::class)
        ->and($importer)->toBeInstanceOf(MeasurementAwareImporter::class);
})->skip(! class_exists(MeasurementImporter::class), 'Measurement importer is not installed');
