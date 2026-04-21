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
                    'message' => 'Hvis du sletter denne mappe, slettes også alle undermapper i den. Denne handling er permanent og kan ikke fortrydes.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Tilføj aktiv',
                    'assign-assets' => 'Tildel aktiver',
                    'assign'        => 'Tildel',
                    'preview-asset' => 'Forhåndsvis aktiv',
                    'preview'       => 'Forhåndsvisning',
                    'remove'        => 'Fjern',
                    'download'      => 'Download',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Filnavn',
                    'tags'           => 'Tags',
                    'property-name'  => 'Egenskabsnavn',
                    'property-value' => 'Egenskabsværdi',
                    'created-at'     => 'Oprettet den',
                    'updated-at'     => 'Opdateret den',
                    'extension'      => 'Filendelse',
                    'path'           => 'Sti',
                    'size'           => 'Størrelse',
                ],

                'directory' => [
                    'title'        => 'Mappe',
                    'create'       => [
                        'title'    => 'Opret mappe',
                        'name'     => 'Navn',
                        'save-btn' => 'Gem mappe',
                    ],

                    'rename' => [
                        'title' => 'Omdøb mappe',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Omdøb aktiv',
                            'save-btn' => 'Gem aktiv',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Slet',
                        'rename'                    => 'Omdøb',
                        'copy'                      => 'Kopiér',
                        'download'                  => 'Download',
                        'download-zip'              => 'Download zip',
                        'paste'                     => 'Indsæt',
                        'add-directory'             => 'Tilføj mappe',
                        'upload-files'              => 'Upload filer',
                        'copy-directory-structured' => 'Kopiér mappestruktur',
                        'get-by-id'                 => 'Hent efter id',
                        'comment'                   => 'Kommentar',
                    ],

                    'linked-resources'                          => 'Tilknyttede ressourcer',
                    'not-found'                                 => 'Ingen mappe fundet',
                    'created-success'                           => 'Mappe oprettet',
                    'updated-success'                           => 'Mappe opdateret',
                    'moved-success'                             => 'Mappe flyttet',
                    'fetch-all-success'                         => 'Mapper hentet',
                    'can-not-deleted'                           => 'Mappen kan ikke slettes, da det er rodmappen.',
                    'deleting-in-progress'                      => 'Sletning af mappe er i gang',
                    'can-not-copy'                              => 'Mappen kan ikke kopieres, da det er rodmappen.',
                    'coping-in-progress'                        => 'Kopiering af mappestruktur er i gang.',
                    'asset-not-found'                           => 'Intet aktiv fundet',
                    'asset-renamed-success'                     => 'Aktiv omdøbt',
                    'asset-moved-success'                       => 'Aktiv flyttet',
                    'asset-name-already-exist'                  => 'Det nye navn findes allerede hos et andet aktiv ved navn :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Aktivets navn er i konflikt med en eksisterende fil i samme mappe.',
                    'old-file-not-found'                        => 'Filen ved stien :old_path blev ikke fundet.',
                    'image-name-is-the-same'                    => 'Dette navn findes allerede. Indtast venligst et andet.',
                    'not-writable'                              => 'Du har ikke tilladelse til at :actionType en :type på denne placering ":path".',
                    'empty-directory'                           => 'Denne mappe er tom.',
                    'failed-download-directory'                 => 'Kunne ikke oprette zip-filen.',
                    'not-allowed'                               => 'Det er ikke tilladt at uploade scriptfiler.',
                ],

                'title'       => 'DAM',
                'description' => 'Værktøjet hjælper dig med at organisere, opbevare og administrere alle dine medieaktiver ét sted',
                'root'        => 'Rod',
                'upload'      => 'Upload',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Aktivegenskaber',
                        'create-btn' => 'Opret egenskab',

                        'datagrid'      => [
                            'name'     => 'Navn',
                            'type'     => 'Type',
                            'language' => 'Sprog',
                            'value'    => 'Værdi',
                            'edit'     => 'Rediger',
                            'delete'   => 'Slet',
                        ],

                        'create'     => [
                            'title'    => 'Opret egenskab',
                            'name'     => 'Navn',
                            'type'     => 'Type',
                            'language' => 'Sprog',
                            'value'    => 'Værdi',
                            'save-btn' => 'Gem',
                        ],
                        'edit' => [
                            'title' => 'Rediger egenskab',
                        ],
                        'delete-success' => 'Aktivegenskab slettet',
                        'create-success' => 'Aktivegenskab oprettet',
                        'update-success' => 'Aktivegenskab opdateret',
                        'not-found'      => 'Egenskab ikke fundet',
                        'found-success'  => 'Egenskab fundet',
                    ],
                ],
                'comments' => [
                    'index'  => 'Tilføj kommentar',
                    'create' => [
                        'create-success' => 'Kommentaren er tilføjet',
                        'create-failure' => 'Kommentaren kunne ikke oprettes',
                    ],
                    'post-comment'    => 'Send kommentar',
                    'post-reply'      => 'Send svar',
                    'reply'           => 'Svar',
                    'add-reply'       => 'Tilføj svar',
                    'add-comment'     => 'Tilføj kommentar',
                    'no-comments'     => 'Ingen kommentarer endnu',
                    'not-found'       => 'Kommentarer ikke fundet',
                    'updated-success' => 'Kommentar opdateret',
                    'update-failed'   => 'Kommentaren kunne ikke opdateres',
                    'delete-success'  => 'Aktivkommentar slettet',
                    'delete-failed'   => 'Aktivkommentaren kunne ikke slettes',
                ],
                'edit' => [
                    'title'              => 'Rediger aktiv',
                    'name'               => 'Navn',
                    'value'              => 'Værdi',
                    'back-btn'           => 'Tilbage',
                    'save-btn'           => 'Gem',
                    'embedded_meta_info' => 'Indlejrede metadata',
                    'custom_meta_info'   => 'Brugerdefinerede metadata',
                    'tags'               => 'Tags',
                    'select-tags'        => 'Vælg eller opret et tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Mappesti',
                    'add_tags'           => 'Tilføj tags',
                    'tab'                => [
                        'preview'          => 'Forhåndsvisning',
                        'properties'       => 'Egenskaber',
                        'comments'         => 'Kommentarer',
                        'linked_resources' => 'Tilknyttede ressourcer',
                        'history'          => 'Historik',
                    ],
                    'button' => [
                        'download'        => 'Download',
                        'custom_download' => 'Brugerdefineret download',
                        'rename'          => 'Omdøb',
                        're_upload'       => 'Upload igen',
                        'delete'          => 'Slet',
                    ],

                    'custom-download' => [
                        'title'              => 'Brugerdefineret download',
                        'format'             => 'Format',
                        'width'              => 'Bredde (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Højde (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Download',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Tagget findes allerede',
                    'image-source-not-readable' => 'Billedkilden kan ikke læses',
                    'failed-to-read'            => 'Kunne ikke læse billedets metadata :exception',
                    'file-re-upload-success'    => 'Filer er blevet uploadet igen.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategori',
                            'product-sku'   => 'Produkt-Sku: ',
                            'category code' => 'Kategorikode: ',
                            'resource-type' => 'Ressourcetype',
                            'resource'      => 'Ressource',
                            'resource-view' => 'Ressourcevisning',
                        ],
                    ],
                    'found-success' => 'Ressource fundet',
                    'not-found'     => 'Ressource ikke fundet',
                ],
                'tags' => [
                    'index'  => 'Tilføj tags',
                    'create' => [
                        'create-success' => 'Tags er blevet tilføjet',
                        'create-failure' => 'Tags kunne ikke oprettes',
                    ],

                    'no-comments'    => 'Ingen tags endnu',
                    'found-success'  => 'Tag fundet',
                    'not-found'      => 'Tags ikke fundet',
                    'update-success' => 'Tags opdateret',
                    'update-failed'  => 'Tags kunne ikke opdateres',
                    'delete-success' => 'Aktivtags fjernet',
                    'delete-failed'  => 'Aktivtags kunne ikke slettes',
                ],
                'delete-success'                          => 'Aktiv slettet',
                'delete-failed-due-to-attached-resources' => 'Aktivet er i brug. Fjern tilknytningen før sletning',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Masseslettet.',
                    'files-upload-success'                => 'Filer uploadet.',
                    'file-upload-success'                 => 'Fil uploadet.',
                    'not-found'                           => 'Fil ikke fundet',
                    'edit-success'                        => 'Fil uploadet',
                    'show-success'                        => 'Fil fundet',
                    'update-success'                      => 'Fil opdateret',
                    'not-found-to-update'                 => 'Filen findes ikke',
                    'not-found-to-destroy'                => 'Filen findes ikke',
                    'files-upload-failed'                 => 'Filer kunne ikke uploades.',
                    'file-upload-failed'                  => 'Filen kunne ikke uploades',
                    'invalid-file'                        => 'Ugyldig fil angivet',
                    'invalid-file-format'                 => 'Ugyldigt format',
                    'invalid-file-format-or-not-provided' => 'Ingen filer angivet eller ugyldigt format.',
                    'download-image-failed'               => 'Kunne ikke hente billedet fra URL',
                    'file-process-failed'                 => 'Nogle filer kunne ikke behandles',
                    'file-forbidden-type'                 => 'Filen har en forbudt type eller filendelse.',
                    'file-too-large'                      => 'Filen er for stor. Den maksimalt tilladte størrelse er :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Aktiv',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Aktiv',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Aktiv',
            'property'         => 'Egenskab',
            'comment'          => 'Kommentar',
            'linked_resources' => 'Tilknyttede ressourcer',
            'directory'        => 'Mappe',
            'tag'              => 'Tag',
            'create'           => 'Opret',
            'edit'             => 'Rediger',
            'update'           => 'Opdater',
            'delete'           => 'Slet',
            'list'             => 'Liste',
            'view'             => 'Vis',
            'upload'           => 'Upload',
            're_upload'        => 'Upload igen',
            'mass_update'      => 'Masseopdatering',
            'mass_delete'      => 'Masseslet',
            'download'         => 'Download',
            'custom_download'  => 'Brugerdefineret download',
            'rename'           => 'Omdøb',
            'move'             => 'Flyt',
            'copy'             => 'Kopiér',
            'copy-structure'   => 'Kopiér mappestruktur',
            'download-zip'     => 'Download zip',
            'asset-assign'     => 'Tildel aktiv',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Feltet :attribute er påkrævet.',
            ],

            'comment' => [
                'required' => 'Kommentarteksten er påkrævet.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Tag-feltet er påkrævet.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Navnefeltet er påkrævet.',
                    'unique'   => 'Navnet er allerede taget.',
                ],
                'language' => [
                    'not-found' => 'Det valgte sprog kunne ikke findes eller er deaktiveret.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Denne handling er ikke autoriseret.',
        ],
    ],
];
