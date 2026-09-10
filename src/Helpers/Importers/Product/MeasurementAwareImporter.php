<?php

namespace Webkul\DAM\Helpers\Importers\Product;

use Webkul\DAM\Helpers\Importers\Product\Concerns\ImportsAssetAttributes;
use Webkul\Measurement\Helpers\Importers\Product\Importer as MeasurementImporter;

class MeasurementAwareImporter extends MeasurementImporter
{
    use ImportsAssetAttributes;
}
