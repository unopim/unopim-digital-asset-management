<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'DAM',
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'Ang pagtanggal sa direktoryong ito ay magtatanggal din ng lahat ng mga subdirektoryo sa loob nito. Ang aksyong ito ay permanente at hindi na mababawi.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Magdagdag ng Asset',
                    'assign-assets' => 'Italaga ang mga Asset',
                    'assign'        => 'Italaga',
                    'preview-asset' => 'I-preview ang Asset',
                    'preview'       => 'Preview',
                    'remove'        => 'Alisin',
                    'download'      => 'I-download',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Pangalan ng File',
                    'tags'           => 'Mga Tag',
                    'property-name'  => 'Pangalan ng Property',
                    'property-value' => 'Halaga ng Property',
                    'created-at'     => 'Ginawa Noong',
                    'updated-at'     => 'Na-update Noong',
                    'extension'      => 'Extension',
                    'path'           => 'Path',
                    'size'           => 'Laki',
                ],

                'directory' => [
                    'title'        => 'Direktoryo',
                    'create'       => [
                        'title'    => 'Gumawa ng Direktoryo',
                        'name'     => 'Pangalan',
                        'save-btn' => 'I-save ang Direktoryo',
                    ],

                    'rename' => [
                        'title' => 'Palitan ang Pangalan ng Direktoryo',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Palitan ang Pangalan ng Asset',
                            'save-btn' => 'I-save ang Asset',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Tanggalin',
                        'rename'                    => 'Palitan ang Pangalan',
                        'copy'                      => 'Kopyahin',
                        'download'                  => 'I-download',
                        'download-zip'              => 'I-download ang Zip',
                        'paste'                     => 'I-paste',
                        'add-directory'             => 'Magdagdag ng Direktoryo',
                        'upload-files'              => 'Mag-upload ng mga File',
                        'copy-directory-structured' => 'Kopyahin ang Istruktura ng Direktoryo',
                        'get-by-id'                 => 'Kunin sa pamamagitan ng Id',
                        'comment'                   => 'Komento',
                    ],

                    'linked-resources'                          => 'Mga Nakaugnay na Resource',
                    'not-found'                                 => 'Walang nahanap na direktoryo',
                    'created-success'                           => 'Matagumpay na nagawa ang direktoryo',
                    'updated-success'                           => 'Matagumpay na na-update ang direktoryo',
                    'moved-success'                             => 'Matagumpay na nailipat ang direktoryo',
                    'fetch-all-success'                         => 'Matagumpay na nakuha ang mga direktoryo',
                    'can-not-deleted'                           => 'Hindi maaaring tanggalin ang direktoryo dahil ito ang Root Directory.',
                    'deleting-in-progress'                      => 'Isinasagawa ang pagtanggal ng direktoryo',
                    'can-not-copy'                              => 'Hindi maaaring kopyahin ang direktoryo dahil ito ang Root Directory.',
                    'coping-in-progress'                        => 'Isinasagawa ang pagkopya ng istruktura ng direktoryo.',
                    'asset-not-found'                           => 'Walang nahanap na asset',
                    'asset-renamed-success'                     => 'Matagumpay na napalitan ang pangalan ng asset',
                    'asset-moved-success'                       => 'Matagumpay na nailipat ang asset',
                    'asset-name-already-exist'                  => 'Ang bagong pangalan ay umiiral na sa ibang asset na pinangalanang :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Ang pangalan ng asset ay nagkakasalungat sa umiiral na file sa parehong direktoryo.',
                    'old-file-not-found'                        => 'Ang file na hiniling sa path na :old_path ay hindi nahanap.',
                    'image-name-is-the-same'                    => 'Umiiral na ang pangalang ito. Mangyaring maglagay ng iba.',
                    'not-writable'                              => 'Hindi ka pinapayagan na :actionType ng :type sa lokasyong ito ":path".',
                    'empty-directory'                           => 'Walang laman ang direktoryong ito.',
                    'failed-download-directory'                 => 'Nabigong gawin ang zip file.',
                    'not-allowed'                               => 'Hindi pinapayagan ang pag-upload ng mga script file.',
                ],

                'title'       => 'DAM',
                'description' => 'Ang tool na ito ay tumutulong sa iyo na ayusin, mag-imbak, at pamahalaan ang lahat ng iyong media asset sa isang lugar',
                'root'        => 'Root',
                'upload'      => 'Mag-upload',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Mga Property ng Asset',
                        'create-btn' => 'Gumawa ng Property',

                        'datagrid'      => [
                            'name'     => 'Pangalan',
                            'type'     => 'Uri',
                            'language' => 'Wika',
                            'value'    => 'Halaga',
                            'edit'     => 'I-edit',
                            'delete'   => 'Tanggalin',
                        ],

                        'create'     => [
                            'title'    => 'Gumawa ng Property',
                            'name'     => 'Pangalan',
                            'type'     => 'Uri',
                            'language' => 'Wika',
                            'value'    => 'Halaga',
                            'save-btn' => 'I-save',
                        ],
                        'edit' => [
                            'title' => 'I-edit ang Property',
                        ],
                        'delete-success' => 'Matagumpay na Natanggal ang Asset Property',
                        'create-success' => 'Matagumpay na Nagawa ang Asset Property',
                        'update-success' => 'Matagumpay na Na-update ang Asset Property',
                        'not-found'      => 'Walang Nahanap na Property',
                        'found-success'  => 'Matagumpay na Nahanap ang Property',
                    ],
                ],
                'comments' => [
                    'index'  => 'Magdagdag ng Komento',
                    'create' => [
                        'create-success' => 'Matagumpay na naidagdag ang komento',
                        'create-failure' => 'Nabigong gumawa ng komento',
                    ],
                    'post-comment'    => 'I-post ang Komento',
                    'post-reply'      => 'I-post ang Tugon',
                    'reply'           => 'Tumugon',
                    'add-reply'       => 'Magdagdag ng Tugon',
                    'add-comment'     => 'Magdagdag ng Komento',
                    'no-comments'     => 'Wala Pang Komento',
                    'not-found'       => 'Walang Nahanap na Komento',
                    'updated-success' => 'Matagumpay na Na-update ang Komento',
                    'update-failed'   => 'Nabigong i-update ang komento',
                    'delete-success'  => 'Matagumpay na Natanggal ang Komento ng Asset',
                    'delete-failed'   => 'Nabigong tanggalin ang komento ng asset',
                ],
                'edit' => [
                    'title'              => 'I-edit ang Asset',
                    'name'               => 'Pangalan',
                    'value'              => 'Halaga',
                    'back-btn'           => 'Bumalik',
                    'save-btn'           => 'I-save',
                    'embedded_meta_info' => 'Naka-embed na Meta Info',
                    'custom_meta_info'   => 'Custom na Meta Info',
                    'tags'               => 'Mga Tag',
                    'select-tags'        => 'Pumili o Gumawa ng Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Path ng Direktoryo',
                    'add_tags'           => 'Magdagdag ng mga Tag',
                    'tab'                => [
                        'preview'          => 'Preview',
                        'properties'       => 'Mga Property',
                        'comments'         => 'Mga Komento',
                        'linked_resources' => 'Mga Nakaugnay na Resource',
                        'history'          => 'Kasaysayan',
                    ],
                    'button' => [
                        'download'        => 'I-download',
                        'custom_download' => 'Custom na Download',
                        'rename'          => 'Palitan ang Pangalan',
                        're_upload'       => 'Muling Mag-upload',
                        'delete'          => 'Tanggalin',
                    ],

                    'custom-download' => [
                        'title'              => 'Custom na Download',
                        'format'             => 'Format',
                        'width'              => 'Lapad (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Taas (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'I-download',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Orihinal',
                        ],
                    ],

                    'tag-already-exists'        => 'Umiiral na ang tag',
                    'image-source-not-readable' => 'Hindi mabasa ang pinagmulan ng larawan',
                    'failed-to-read'            => 'Nabigong basahin ang metadata ng larawan :exception',
                    'file-re-upload-success'    => 'Matagumpay na Muling Na-upload ang mga File.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkto',
                            'category'      => 'Kategorya',
                            'product-sku'   => 'Sku ng Produkto: ',
                            'category code' => 'Code ng Kategorya: ',
                            'resource-type' => 'Uri ng Resource',
                            'resource'      => 'Resource',
                            'resource-view' => 'View ng Resource',
                        ],
                    ],
                    'found-success' => 'Matagumpay na Nahanap ang Resource',
                    'not-found'     => 'Walang Nahanap na Resource',
                ],
                'tags' => [
                    'index'  => 'Magdagdag ng mga tag',
                    'create' => [
                        'create-success' => 'Matagumpay na naidagdag ang mga tag',
                        'create-failure' => 'Nabigong gumawa ng mga tag',
                    ],

                    'no-comments'    => 'Wala Pang Tag',
                    'found-success'  => 'Matagumpay na Nahanap ang Tag',
                    'not-found'      => 'Walang Nahanap na mga Tag',
                    'update-success' => 'Matagumpay na Na-update ang mga Tag',
                    'update-failed'  => 'Nabigong i-update ang mga tag',
                    'delete-success' => 'Matagumpay na Naalis ang mga Tag ng Asset',
                    'delete-failed'  => 'Nabigong tanggalin ang mga tag ng asset',
                ],
                'delete-success'                          => 'Matagumpay na natanggal ang asset',
                'delete-failed-due-to-attached-resources' => 'Ginagamit ang asset. I-unlink bago tanggalin',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Matagumpay na Natanggal nang Maramihan.',
                    'files-upload-success'                => 'Matagumpay na Na-upload ang mga File.',
                    'file-upload-success'                 => 'Matagumpay na Na-upload ang File.',
                    'not-found'                           => 'Walang Nahanap na File',
                    'edit-success'                        => 'Matagumpay na Na-upload ang File',
                    'show-success'                        => 'Matagumpay na Nahanap ang File',
                    'update-success'                      => 'Matagumpay na Na-update ang File',
                    'not-found-to-update'                 => 'Hindi umiiral ang File',
                    'not-found-to-destroy'                => 'Hindi umiiral ang File',
                    'files-upload-failed'                 => 'Nabigong i-upload ang mga file.',
                    'file-upload-failed'                  => 'Nabigong i-upload ang file',
                    'invalid-file'                        => 'Hindi Wastong File ang Ibinigay',
                    'invalid-file-format'                 => 'Hindi Wastong Format',
                    'invalid-file-format-or-not-provided' => 'Walang ibinigay na file o hindi wastong format.',
                    'download-image-failed'               => 'Nabigong i-download ang larawan mula sa URL',
                    'file-process-failed'                 => 'Nabigong maproseso ang ilang mga file',
                    'file-forbidden-type'                 => 'Ang file ay may ipinagbabawal na uri o extension.',
                    'file-too-large'                      => 'Napakalaki ng file. Ang pinakamataas na pinapayagang laki ay :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Asset',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Asset',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Asset',
            'property'         => 'Property',
            'comment'          => 'Komento',
            'linked_resources' => 'Mga Nakaugnay na Resource',
            'directory'        => 'Direktoryo',
            'tag'              => 'Tag',
            'create'           => 'Gumawa',
            'edit'             => 'I-edit',
            'update'           => 'I-update',
            'delete'           => 'Tanggalin',
            'list'             => 'Listahan',
            'view'             => 'Tingnan',
            'upload'           => 'Mag-upload',
            're_upload'        => 'Muling Mag-upload',
            'mass_update'      => 'Maramihang Update',
            'mass_delete'      => 'Maramihang Pagtanggal',
            'download'         => 'I-download',
            'custom_download'  => 'Custom na Download',
            'rename'           => 'Palitan ang Pangalan',
            'move'             => 'Ilipat',
            'copy'             => 'Kopyahin',
            'copy-structure'   => 'Kopyahin ang Istruktura ng Direktoryo',
            'download-zip'     => 'I-download ang Zip',
            'asset-assign'     => 'Italaga ang Asset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Ang field na :attribute ay kinakailangan.',
            ],

            'comment' => [
                'required' => 'Kinakailangan ang mensahe ng Komento.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Kinakailangan ang field ng Tag.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Kinakailangan ang field na Pangalan.',
                    'unique'   => 'Nakuha na ang Pangalan.',
                ],
                'language' => [
                    'not-found' => 'Ang napiling wika ay hindi nahanap o kasalukuyang hindi pinagana.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Hindi awtorisado ang aksyong ito.',
        ],
    ],
];
