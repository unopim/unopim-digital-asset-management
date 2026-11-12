<?php

use Webkul\DAM\Helpers\Exporters\Asset\Exporter;
use Webkul\DAM\Helpers\Importers\Asset\Importer;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Validators\JobInstances\Export\AssetJobValidator;

return [
    Importer::ENTITY_TYPE => [
        'title'       => 'dam::app.data-transfer.exporters.assets.title',
        'exporter'    => Exporter::class,
        'source'      => AssetRepository::class,
        'sample_path' => 'data-transfer/samples/import/assets.csv',
        'validator'   => AssetJobValidator::class,
        'filters'     => [
            'fields' => [
                [
                    'name'       => 'file_format',
                    'title'      => 'data_transfer::app.exporters.fields.file-format',
                    'type'       => 'select',
                    'required'   => true,
                    'validation' => 'required',
                    'options'    => [
                        [
                            'value' => 'Csv',
                            'label' => 'CSV',
                        ], [
                            'value' => 'Xls',
                            'label' => 'XLS',
                        ], [
                            'value' => 'Xlsx',
                            'label' => 'XLSX',
                        ],
                    ],
                ], [
                    'name'       => 'file_type',
                    'title'      => 'dam::app.data-transfer.exporters.assets.filters.file-type',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.dam.data_transfer.export_filters.file_types',
                    'track_by'   => 'value',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'directories',
                    'title'      => 'dam::app.data-transfer.exporters.assets.filters.directories',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.dam.data_transfer.export_filters.directories',
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                ], [
                    'name'       => 'tags',
                    'title'      => 'dam::app.data-transfer.exporters.assets.filters.tags',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'list_route' => 'admin.dam.data_transfer.export_filters.tags',
                    'track_by'   => 'name',
                    'label_by'   => 'label',
                ], [
                    'name'     => 'with_media',
                    'title'    => 'data_transfer::app.exporters.fields.with-media',
                    'required' => false,
                    'type'     => 'boolean',
                ],
            ],
        ],
    ],
];
