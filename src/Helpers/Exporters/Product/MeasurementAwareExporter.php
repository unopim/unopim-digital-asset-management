<?php

namespace Webkul\DAM\Helpers\Exporters\Product;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DAM\Helpers\Exporters\Product\Concerns\ExportsAssetAttributes;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Measurement\Helpers\Exporters\ProductExporter as MeasurementExporter;

class MeasurementAwareExporter extends MeasurementExporter
{
    use ExportsAssetAttributes;

    public function __construct(
        JobTrackBatchRepository $exportBatchRepository,
        FileExportFileBuffer $exportFileBuffer,
        ChannelRepository $channelRepository,
        AttributeRepository $attributeRepository,
        ProductSource $productSource,
        protected AssetRepository $assetRepository,
    ) {
        parent::__construct(
            $exportBatchRepository,
            $exportFileBuffer,
            $channelRepository,
            $attributeRepository,
            $productSource
        );
    }
}
