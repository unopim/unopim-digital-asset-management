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
                    'message' => 'Hvis du sletter denne mappen, slettes også alle undermapper i den. Denne handlingen er permanent og kan ikke angres.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Legg til ressurs',
                    'assign-assets' => 'Tildel ressurser',
                    'assign'        => 'Tildel',
                    'preview-asset' => 'Forhåndsvis ressurs',
                    'preview'       => 'Forhåndsvisning',
                    'remove'        => 'Fjern',
                    'download'      => 'Last ned',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Filnavn',
                    'tags'           => 'Tagger',
                    'property-name'  => 'Egenskapsnavn',
                    'property-value' => 'Egenskapsverdi',
                    'created-at'     => 'Opprettet',
                    'updated-at'     => 'Oppdatert',
                    'extension'      => 'Filtype',
                    'path'           => 'Sti',
                    'size'           => 'Størrelse',
                ],

                'directory' => [
                    'title'        => 'Mappe',
                    'create'       => [
                        'title'    => 'Opprett mappe',
                        'name'     => 'Navn',
                        'save-btn' => 'Lagre mappe',
                    ],

                    'rename' => [
                        'title' => 'Gi mappen nytt navn',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Gi ressursen nytt navn',
                            'save-btn' => 'Lagre ressurs',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Slett',
                        'rename'                    => 'Gi nytt navn',
                        'copy'                      => 'Kopier',
                        'download'                  => 'Last ned',
                        'download-zip'              => 'Last ned zip',
                        'paste'                     => 'Lim inn',
                        'add-directory'             => 'Legg til mappe',
                        'upload-files'              => 'Last opp filer',
                        'copy-directory-structured' => 'Kopier mappestruktur',
                        'get-by-id'                 => 'Hent etter ID',
                        'comment'                   => 'Kommentar',
                    ],

                    'linked-resources'                          => 'Tilknyttede ressurser',
                    'not-found'                                 => 'Ingen mappe funnet',
                    'created-success'                           => 'Mappe opprettet',
                    'updated-success'                           => 'Mappe oppdatert',
                    'moved-success'                             => 'Mappe flyttet',
                    'fetch-all-success'                         => 'Mapper hentet',
                    'can-not-deleted'                           => 'Mappen kan ikke slettes fordi den er rotmappen.',
                    'deleting-in-progress'                      => 'Sletting av mappe pågår',
                    'can-not-copy'                              => 'Mappen kan ikke kopieres fordi den er rotmappen.',
                    'coping-in-progress'                        => 'Kopiering av mappestruktur pågår.',
                    'asset-not-found'                           => 'Ingen ressurs funnet',
                    'asset-renamed-success'                     => 'Ressursen fikk nytt navn',
                    'asset-moved-success'                       => 'Ressursen er flyttet',
                    'asset-name-already-exist'                  => 'Det nye navnet finnes allerede på en annen ressurs med navnet :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Ressursnavnet er i konflikt med en eksisterende fil i samme mappe.',
                    'old-file-not-found'                        => 'Filen som ble forespurt på stien :old_path ble ikke funnet.',
                    'image-name-is-the-same'                    => 'Dette navnet finnes allerede. Vennligst oppgi et annet.',
                    'not-writable'                              => 'Du har ikke tillatelse til å :actionType en :type på denne plasseringen ":path".',
                    'empty-directory'                           => 'Denne mappen er tom.',
                    'failed-download-directory'                 => 'Kunne ikke opprette zip-filen.',
                    'not-allowed'                               => 'Opplasting av skriptfiler er ikke tillatt.',
                ],

                'title'       => 'DAM',
                'description' => 'Verktøyet hjelper deg med å organisere, lagre og administrere alle medieressursene dine på ett sted',
                'root'        => 'Rot',
                'upload'      => 'Last opp',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Ressursegenskaper',
                        'create-btn' => 'Opprett egenskap',

                        'datagrid'      => [
                            'name'     => 'Navn',
                            'type'     => 'Type',
                            'language' => 'Språk',
                            'value'    => 'Verdi',
                            'edit'     => 'Rediger',
                            'delete'   => 'Slett',
                        ],

                        'create'     => [
                            'title'    => 'Opprett egenskap',
                            'name'     => 'Navn',
                            'type'     => 'Type',
                            'language' => 'Språk',
                            'value'    => 'Verdi',
                            'save-btn' => 'Lagre',
                        ],
                        'edit' => [
                            'title' => 'Rediger egenskap',
                        ],
                        'delete-success' => 'Ressursegenskap slettet',
                        'create-success' => 'Ressursegenskap opprettet',
                        'update-success' => 'Ressursegenskap oppdatert',
                        'not-found'      => 'Egenskap ikke funnet',
                        'found-success'  => 'Egenskap funnet',
                    ],
                ],
                'comments' => [
                    'index'  => 'Legg til kommentar',
                    'create' => [
                        'create-success' => 'Kommentaren er lagt til',
                        'create-failure' => 'Kunne ikke opprette kommentaren',
                    ],
                    'post-comment'    => 'Send kommentar',
                    'post-reply'      => 'Send svar',
                    'reply'           => 'Svar',
                    'add-reply'       => 'Legg til svar',
                    'add-comment'     => 'Legg til kommentar',
                    'no-comments'     => 'Ingen kommentarer ennå',
                    'not-found'       => 'Kommentarer ikke funnet',
                    'updated-success' => 'Kommentar oppdatert',
                    'update-failed'   => 'Kunne ikke oppdatere kommentaren',
                    'delete-success'  => 'Ressurskommentar slettet',
                    'delete-failed'   => 'Kunne ikke slette ressurskommentaren',
                ],
                'edit' => [
                    'title'              => 'Rediger ressurs',
                    'name'               => 'Navn',
                    'value'              => 'Verdi',
                    'back-btn'           => 'Tilbake',
                    'save-btn'           => 'Lagre',
                    'embedded_meta_info' => 'Innebygd metainformasjon',
                    'custom_meta_info'   => 'Tilpasset metainformasjon',
                    'tags'               => 'Tagger',
                    'select-tags'        => 'Velg eller opprett en tagg',
                    'tag'                => 'Tagg',
                    'directory-path'     => 'Mappesti',
                    'add_tags'           => 'Legg til tagger',
                    'tab'                => [
                        'preview'          => 'Forhåndsvisning',
                        'properties'       => 'Egenskaper',
                        'comments'         => 'Kommentarer',
                        'linked_resources' => 'Tilknyttede ressurser',
                        'history'          => 'Historikk',
                    ],
                    'button' => [
                        'download'        => 'Last ned',
                        'custom_download' => 'Tilpasset nedlasting',
                        'rename'          => 'Gi nytt navn',
                        're_upload'       => 'Last opp på nytt',
                        'delete'          => 'Slett',
                    ],

                    'custom-download' => [
                        'title'              => 'Tilpasset nedlasting',
                        'format'             => 'Format',
                        'width'              => 'Bredde (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Høyde (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Last ned',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Taggen finnes allerede',
                    'image-source-not-readable' => 'Bildekilden kan ikke leses',
                    'failed-to-read'            => 'Kunne ikke lese bildets metadata :exception',
                    'file-re-upload-success'    => 'Filene ble lastet opp på nytt.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategori',
                            'product-sku'   => 'Produkt-Sku: ',
                            'category code' => 'Kategorikode: ',
                            'resource-type' => 'Ressurstype',
                            'resource'      => 'Ressurs',
                            'resource-view' => 'Ressursvisning',
                        ],
                    ],
                    'found-success' => 'Ressurs funnet',
                    'not-found'     => 'Ressurs ikke funnet',
                ],
                'tags' => [
                    'index'  => 'Legg til tagger',
                    'create' => [
                        'create-success' => 'Taggene er lagt til',
                        'create-failure' => 'Kunne ikke opprette taggene',
                    ],

                    'no-comments'    => 'Ingen tagger ennå',
                    'found-success'  => 'Tagg funnet',
                    'not-found'      => 'Tagger ikke funnet',
                    'update-success' => 'Tagger oppdatert',
                    'update-failed'  => 'Kunne ikke oppdatere taggene',
                    'delete-success' => 'Ressurstagger fjernet',
                    'delete-failed'  => 'Kunne ikke slette ressurstaggene',
                ],
                'delete-success'                          => 'Ressurs slettet',
                'delete-failed-due-to-attached-resources' => 'Ressursen er i bruk. Fjern tilknytningen før du sletter',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Masseslettet.',
                    'files-upload-success'                => 'Filer lastet opp.',
                    'file-upload-success'                 => 'Fil lastet opp.',
                    'not-found'                           => 'Fil ikke funnet',
                    'edit-success'                        => 'Fil lastet opp',
                    'show-success'                        => 'Fil funnet',
                    'update-success'                      => 'Fil oppdatert',
                    'not-found-to-update'                 => 'Filen finnes ikke',
                    'not-found-to-destroy'                => 'Filen finnes ikke',
                    'files-upload-failed'                 => 'Kunne ikke laste opp filene.',
                    'file-upload-failed'                  => 'Kunne ikke laste opp filen',
                    'invalid-file'                        => 'Ugyldig fil oppgitt',
                    'invalid-file-format'                 => 'Ugyldig format',
                    'invalid-file-format-or-not-provided' => 'Ingen filer oppgitt eller ugyldig format.',
                    'download-image-failed'               => 'Kunne ikke laste ned bildet fra URL',
                    'file-process-failed'                 => 'Noen filer kunne ikke behandles',
                    'file-forbidden-type'                 => 'Filen har en forbudt type eller filtype.',
                    'file-too-large'                      => 'Filen er for stor. Maksimal tillatt størrelse er :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Ressurs',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Ressurs',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Ressurs',
            'property'         => 'Egenskap',
            'comment'          => 'Kommentar',
            'linked_resources' => 'Tilknyttede ressurser',
            'directory'        => 'Mappe',
            'tag'              => 'Tagg',
            'create'           => 'Opprett',
            'edit'             => 'Rediger',
            'update'           => 'Oppdater',
            'delete'           => 'Slett',
            'list'             => 'Liste',
            'view'             => 'Vis',
            'upload'           => 'Last opp',
            're_upload'        => 'Last opp på nytt',
            'mass_update'      => 'Masseoppdatering',
            'mass_delete'      => 'Masseslett',
            'download'         => 'Last ned',
            'custom_download'  => 'Tilpasset nedlasting',
            'rename'           => 'Gi nytt navn',
            'move'             => 'Flytt',
            'copy'             => 'Kopier',
            'copy-structure'   => 'Kopier mappestruktur',
            'download-zip'     => 'Last ned zip',
            'asset-assign'     => 'Tildel ressurs',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Feltet :attribute er påkrevd.',
            ],

            'comment' => [
                'required' => 'Kommentarmeldingen er påkrevd.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Tagg-feltet er påkrevd.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Navnefeltet er påkrevd.',
                    'unique'   => 'Navnet er allerede tatt.',
                ],
                'language' => [
                    'not-found' => 'Det valgte språket ble ikke funnet eller er for øyeblikket deaktivert.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Denne handlingen er ikke autorisert.',
        ],
    ],
];
