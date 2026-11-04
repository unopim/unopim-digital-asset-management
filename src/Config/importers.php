<?php

use Webkul\DAM\Helpers\Importers\Asset\Importer;
use Webkul\DAM\Validators\JobInstances\Import\AssetJobValidator;

return [
    'assets' => [
        'title'            => 'dam::app.data-transfer.importers.assets.title',
        'importer'         => Importer::class,
        'sample_path'      => 'data-transfer/samples/import/assets.csv',
        'validator'        => AssetJobValidator::class,
        'has_file_options' => true,
        'samples'          => [
            'bundle' => [
                'path'  => 'data-transfer/samples/import/assets-bundle.zip',
                'label' => 'dam::app.data-transfer.importers.assets.samples.bundle',
            ],
            'media' => [
                'path'  => 'data-transfer/samples/import/assets-media-bundle.zip',
                'label' => 'dam::app.data-transfer.importers.assets.samples.media',
            ],
        ],
    ],
];
