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
                    'message' => 'Het verwijderen van deze map zal ook alle submappen erin verwijderen. Deze actie is definitief en kan niet ongedaan worden gemaakt.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Asset Toevoegen',
                    'assign-assets' => 'Assets Toewijzen',
                    'assign'        => 'Toewijzen',
                    'preview-asset' => 'Asset Voorbeeld',
                    'preview'       => 'Voorbeeld',
                    'remove'        => 'Verwijderen',
                    'download'      => 'Downloaden',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Bestandsnaam',
                    'tags'           => 'Tags',
                    'property-name'  => 'Eigenschapsnaam',
                    'property-value' => 'Eigenschapswaarde',
                    'created-at'     => 'Aangemaakt op',
                    'updated-at'     => 'Bijgewerkt op',
                    'extension'      => 'Extensie',
                    'path'           => 'Pad',
                ],

                'directory' => [
                    'title'        => 'Map',
                    'create'       => [
                        'title'    => 'Map Aanmaken',
                        'name'     => 'Naam',
                        'save-btn' => 'Map Opslaan',
                    ],

                    'rename' => [
                        'title' => 'Map Hernoemen',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Asset Hernoemen',
                            'save-btn' => 'Asset Opslaan',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Verwijderen',
                        'rename'                    => 'Hernoemen',
                        'copy'                      => 'Kopiëren',
                        'download'                  => 'Downloaden',
                        'download-zip'              => 'Zip Downloaden',
                        'paste'                     => 'Plakken',
                        'add-directory'             => 'Map Toevoegen',
                        'upload-files'              => 'Bestanden Uploaden',
                        'copy-directory-structured' => 'Mapstructuur Kopiëren',
                    ],

                    'not-found'                                 => 'Geen map gevonden',
                    'created-success'                           => 'Map succesvol aangemaakt',
                    'updated-success'                           => 'Map succesvol bijgewerkt',
                    'moved-success'                             => 'Map succesvol verplaatst',
                    'can-not-deleted'                           => 'Map kan niet worden verwijderd omdat het de hoofdmap is.',
                    'deleting-in-progress'                      => 'Map verwijderen in uitvoering',
                    'can-not-copy'                              => 'Map kan niet worden gekopieerd omdat het de hoofdmap is.',
                    'coping-in-progress'                        => 'Mapstructuur kopiëren in uitvoering.',
                    'asset-not-found'                           => 'Geen asset gevonden',
                    'asset-renamed-success'                     => 'Asset succesvol hernoemd',
                    'asset-moved-success'                       => 'Asset succesvol verplaatst',
                    'asset-name-already-exist'                  => 'De nieuwe naam bestaat al voor een andere asset genaamd :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'De assetnaam conflicteert met een bestaand bestand in dezelfde map.',
                    'old-file-not-found'                        => 'Het bestand op pad :old_path is niet gevonden.',
                    'image-name-is-the-same'                    => 'Deze naam bestaat al. Voer een andere in.',
                    'not-writable'                              => 'U mag geen :actionType :type uitvoeren op deze locatie ":path".',
                    'empty-directory'                           => 'Deze map is leeg.',
                    'failed-download-directory'                 => 'Het maken van het zipbestand is mislukt.',
                ],

                'title'       => 'DAM',
                'description' => 'Deze tool helpt u om al uw media-assets op één plek te organiseren, op te slaan en te beheren.',
                'root'        => 'Hoofdmap',
                'upload'      => 'Uploaden',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Activa-eigenschappen',
                        'create-btn' => 'Eigenschap maken',

                        'datagrid'      => [
                            'name'     => 'Naam',
                            'type'     => 'Type',
                            'language' => 'Taal',
                            'value'    => 'Waarde',
                            'edit'     => 'Bewerken',
                            'delete'   => 'Verwijderen',
                        ],

                        'create'     => [
                            'title'    => 'Eigenschap maken',
                            'name'     => 'Naam',
                            'type'     => 'Type',
                            'language' => 'Taal',
                            'value'    => 'Waarde',
                            'save-btn' => 'Opslaan',
                        ],
                        'edit' => [
                            'title' => 'Eigenschap bewerken',
                        ],
                        'delete-success' => 'Asset Property succesvol verwijderd',
                        'create-success' => 'Asset Property succesvol aangemaakt',
                        'update-success' => 'Asset Property succesvol bijgewerkt',
                    ],
                ],
                'comments' => [
                    'index'  => 'Voeg commentaar toe',

                    'create' => [
                        'create-success' => 'Commentaar is succesvol toegevoegd',
                    ],

                    'post-comment' => 'Plaats een reactie',
                    'post-reply'   => 'Reactie plaatsen',
                    'reply'        => 'Antwoord',
                    'add-reply'    => 'Antwoord toevoegen',
                    'add-comment'  => 'Reactie toevoegen',
                    'no-comments'  => 'Nog geen reacties',
                ],
                'edit' => [
                    'title'              => 'Bewerk Asset',
                    'name'               => 'Naam',
                    'value'              => 'Waarde',
                    'back-btn'           => 'Terug',
                    'save-btn'           => 'Opslaan',
                    'embedded_meta_info' => 'Ingesloten Meta-info',
                    'custom_meta_info'   => 'Aangepaste Meta-info',
                    'tags'               => 'Tags',
                    'select-tags'        => 'Kies of maak een tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Directorypad',
                    'add_tags'           => 'Tags toevoegen',
                    'tab'                => [
                        'preview'          => 'Preview',
                        'properties'       => 'Eigenschappen',
                        'comments'         => 'Reacties',
                        'linked_resources' => 'Gelinkte bronnen',
                        'history'          => 'Geschiedenis',
                    ],
                    'button' => [
                        'download'        => 'Downloaden',
                        'custom_download' => 'Aangepaste download',
                        'rename'          => 'Hernoemen',
                        're_upload'       => 'Opnieuw uploaden',
                        'delete'          => 'Verwijderen',
                    ],

                    'custom-download' => [
                        'title'              => 'Aangepast downloaden',
                        'format'             => 'Formaat',
                        'width'              => 'Breedte (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Hoogte (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Downloaden',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Origineel',
                        ],
                    ],

                    'tag-already-exists'        => 'Tag bestaat al',
                    'image-source-not-readable' => 'Afbeeldingsbron niet leesbaar',
                    'failed-to-read'            => 'Kan afbeeldingsmetadata niet lezen :exception',
                    'file_re_upload_success'    => 'Bestanden opnieuw geüpload.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Product',
                            'category'      => 'Categorie',
                            'product-sku'   => 'Product Sku: ',
                            'category code' => 'Categoriecode: ',
                            'resource-type' => 'Resourcetype',
                            'resource'      => 'Resource',
                            'resource-view' => 'Resourceweergave',
                        ],
                    ],
                ],
                'delete-success'                          => 'Asset succesvol verwijderd',
                'delete-failed-due-to-attached-resources' => 'Het verwijderen van de asset is mislukt omdat deze is gekoppeld aan resources (Assetnaam: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Massa succesvol verwijderd.',
                    'files_upload_success' => 'Bestanden succesvol geüpload.',
                    'file_upload_success'  => 'Bestand succesvol geüpload.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Bezit',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Bezit',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Asset',
            'property'         => 'Eigendom',
            'comment'          => 'Commentaar',
            'linked_resources' => 'Gekoppelde bronnen',
            'directory'        => 'Directory',
            'tag'              => 'Tag',
            'create'           => 'Maken',
            'edit'             => 'Bewerken',
            'update'           => 'Update',
            'delete'           => 'Verwijderen',
            'list'             => 'Lijst',
            'view'             => 'Weergeven',
            'upload'           => 'Uploaden',
            're_upload'        => 'Opnieuw uploaden',
            'mass_update'      => 'Mass Update',
            'mass_delete'      => 'Mass Delete',
            'download'         => 'Downloaden',
            'custom_download'  => 'Aangepaste download',
            'rename'           => 'Hernoemen',
            'move'             => 'Verplaatsen',
            'copy'             => 'Kopiëren',
            'copy-structure'   => 'Kopiëren Directorystructuur',
            'download-zip'     => 'Download Zip',
            'asset-assign'     => 'Assign Asset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Het veld :attribute is verplicht.',
            ],

            'comment' => [
                'required' => 'Het bericht \'Reactie\' is verplicht.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Het veld Naam is verplicht.',
                    'unique'   => 'De naam is al in gebruik.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Deze actie is niet toegestaan.',
        ],
    ],
];
