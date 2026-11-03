<?php

use Webkul\DAM\Helpers\Exporters\Product\Exporter as DamProductExporter;
use Webkul\DAM\Helpers\Importers\Product\Importer as DamProductImporter;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreProductExporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreProductImporter;

it('resolves the core product exporter and importer to the DAM overrides after boot', function () {
    expect(app(CoreProductExporter::class))->toBeInstanceOf(DamProductExporter::class)
        ->and(app(CoreProductImporter::class))->toBeInstanceOf(DamProductImporter::class);
});
