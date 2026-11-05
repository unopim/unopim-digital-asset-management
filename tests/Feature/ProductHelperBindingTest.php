<?php

use Webkul\DAM\Helpers\Exporters\Product\Exporter as DamProductExporter;
use Webkul\DAM\Helpers\Exporters\Product\MeasurementAwareExporter;
use Webkul\DAM\Helpers\Importers\Product\Importer as DamProductImporter;
use Webkul\DAM\Helpers\Importers\Product\MeasurementAwareImporter;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreProductExporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreProductImporter;

it('resolves the core product exporter and importer to the DAM overrides after boot', function () {
    $exporter = class_exists('Webkul\\Measurement\\Helpers\\Exporters\\ProductExporter')
        ? MeasurementAwareExporter::class
        : DamProductExporter::class;

    $importer = class_exists('Webkul\\Measurement\\Helpers\\Importers\\Product\\Importer')
        ? MeasurementAwareImporter::class
        : DamProductImporter::class;

    expect(app(CoreProductExporter::class))->toBeInstanceOf($exporter)
        ->and(app(CoreProductImporter::class))->toBeInstanceOf($importer);
});
