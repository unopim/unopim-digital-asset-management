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
                    'message' => 'Het verwijderen van deze map zal ook alle submappen erin verwijderen. Deze actie is permanent en kan niet ongedaan worden gemaakt.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Voeg Asset Toe',
                    'assign-assets' => 'Ken Assets Toe',
                    'assign'        => 'Toewijzen',
                    'preview-asset' => 'Bekijk Asset',
                    'preview'       => 'Voorvertoning',
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
                    'created-at'     => 'Gemaakt op',
                    'updated-at'     => 'Bijgewerkt op',
                    'extension'      => 'Extensie',
                    'path'           => 'Pad',
                    'size'           => 'Grootte',
                ],

                'directory' => [
                    'title'        => 'Map',
                    'create'       => [
                        'title'    => 'Maak Map Aan',
                        'name'     => 'Naam',
                        'save-btn' => 'Map Opslaan',
                    ],

                    'rename' => [
                        'title' => 'Hernoem Map',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Hernoem Asset',
                            'save-btn' => 'Asset Opslaan',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Verwijderen',
                        'rename'                    => 'Hernoemen',
                        'copy'                      => 'Kopiëren',
                        'download'                  => 'Downloaden',
                        'download-zip'              => 'Download Zip',
                        'paste'                     => 'Plakken',
                        'add-directory'             => 'Voeg Map Toe',
                        'upload-files'              => 'Bestanden Uploaden',
                        'copy-directory-structured' => 'Mapstructuur Kopiëren',
                    ],

                    'not-found'                                 => 'Geen map gevonden',
                    'created-success'                           => 'Map succesvol aangemaakt',
                    'updated-success'                           => 'Map succesvol bijgewerkt',
                    'moved-success'                             => 'Map succesvol verplaatst',
                    'fetch-all-success'                         => 'Mappen succesvol opgehaald',
                    'can-not-deleted'                           => 'Map kan niet worden verwijderd, omdat het de rootmap is.',
                    'deleting-in-progress'                      => 'Map wordt verwijderd',
                    'can-not-copy'                              => 'Map kan niet worden gekopieerd, omdat het de rootmap is.',
                    'coping-in-progress'                        => 'Mapstructuur wordt gekopieerd.',
                    'asset-not-found'                           => 'Geen asset gevonden',
                    'asset-renamed-success'                     => 'Asset succesvol hernoemd',
                    'asset-moved-success'                       => 'Asset succesvol verplaatst',
                    'asset-name-already-exist'                  => 'De nieuwe naam bestaat al bij een ander asset met de naam :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'De assetnaam komt in conflict met een bestaand bestand in dezelfde map.',
                    'old-file-not-found'                        => 'Het bestand op het pad :old_path werd niet gevonden.',
                    'image-name-is-the-same'                    => 'Deze naam bestaat al. Voer een andere naam in.',
                    'not-writable'                              => 'Je mag geen :actionType een :type op deze locatie ":path".',
                    'empty-directory'                           => 'Deze map is leeg.',
                    'failed-download-directory'                 => 'Mislukt om het zipbestand te maken.',
                    'not-allowed'                               => 'Het uploaden van scriptbestanden is niet toegestaan.',
                ],

                'title'       => 'DAM',
                'description' => 'Deze tool helpt je om al je mediabestanden op één plaats te organiseren, op te slaan en te beheren.',
                'root'        => 'Root',
                'upload'      => 'Uploaden',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Asset Eigenschappen',
                        'create-btn' => 'Maak Eigenschap Aan',

                        'datagrid'      => [
                            'name'     => 'Naam',
                            'type'     => 'Type',
                            'language' => 'Taal',
                            'value'    => 'Waarde',
                            'edit'     => 'Bewerken',
                            'delete'   => 'Verwijderen',
                        ],

                        'create'     => [
                            'title'    => 'Maak Eigenschap Aan',
                            'name'     => 'Naam',
                            'type'     => 'Type',
                            'language' => 'Taal',
                            'value'    => 'Waarde',
                            'save-btn' => 'Opslaan',
                        ],
                        'edit' => [
                            'title' => 'Bewerk Eigenschap',
                        ],
                        'delete-success' => 'Asset Eigenschap succesvol verwijderd',
                        'create-success' => 'Asset Eigenschap succesvol aangemaakt',
                        'update-success' => 'Asset Eigenschap succesvol bijgewerkt',
                        'not-found'      => 'Eigenschap niet gevonden',
                        'found-success'  => 'Eigenschap succesvol gevonden',

                    ],
                ],
                'comments' => [
                    'index'  => 'Voeg Reactie Toe',
                    'create' => [
                        'create-success' => 'Reactie is succesvol toegevoegd',
                        'create-failure' => 'Reactie kon niet worden aangemaakt',
                    ],
                    'post-comment'    => 'Plaats Reactie',
                    'post-reply'      => 'Plaats Antwoord',
                    'reply'           => 'Antwoord',
                    'add-reply'       => 'Voeg Antwoord Toe',
                    'add-comment'     => 'Voeg Reactie Toe',
                    'no-comments'     => 'Nog Geen Reacties',
                    'not-found'       => 'Reacties niet gevonden',
                    'updated-success' => 'Reactie succesvol bijgewerkt',
                    'update-failed'   => 'Reactie kon niet worden bijgewerkt',
                    'delete-success'  => 'Asset Reactie succesvol verwijderd',
                    'delete-failed'   => 'Asset Reactie kon niet worden verwijderd',
                ],
                'edit' => [
                    'title'              => 'Bewerk Asset',
                    'name'               => 'Naam',
                    'value'              => 'Waarde',
                    'back-btn'           => 'Terug',
                    'save-btn'           => 'Opslaan',
                    'embedded_meta_info' => 'Ingebedde Meta-informatie',
                    'custom_meta_info'   => 'Aangepaste Meta-informatie',
                    'tags'               => 'Tags',
                    'select-tags'        => 'Kies of Maak een Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Mappad',
                    'add_tags'           => 'Voeg Tags Toe',
                    'tab'                => [
                        'preview'          => 'Voorbeeld',
                        'properties'       => 'Eigenschappen',
                        'comments'         => 'Opmerkingen',
                        'linked_resources' => 'Gekoppelde Bronnen',
                        'history'          => 'Geschiedenis',
                    ],
                    'button' => [
                        'download'        => 'Downloaden',
                        'custom_download' => 'Aangepaste Download',
                        'rename'          => 'Hernoemen',
                        're_upload'       => 'Opnieuw Uploaden',
                        'delete'          => 'Verwijderen',
                    ],

                    'custom-download' => [
                        'title'              => 'Aangepaste Download',
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
                    'failed-to-read'            => 'Het lezen van de afbeelding is mislukt :exception',
                    'file-re-upload-success'    => 'Bestanden succesvol opnieuw geüpload.',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Product',
                            'category'      => 'Categorie',
                            'product-sku'   => 'Product SKU: ',
                            'category code' => 'Categoriecode: ',
                            'resource-type' => 'Brontype',
                            'resource'      => 'Bron',
                            'resource-view' => 'Bronweergave',
                        ],
                    ],
                    'found-success' => 'Bron succesvol gevonden',
                    'not-found'     => 'Bron niet gevonden',
                ],
                'tags' => [
                    'index'  => 'Voeg tags toe',
                    'create' => [
                        'create-success' => 'Tags zijn succesvol toegevoegd',
                        'create-failure' => 'Tags konden niet worden aangemaakt',
                    ],

                    'no-comments'    => 'Nog geen Tags',
                    'found-success'  => 'Tag succesvol gevonden',
                    'not-found'      => 'Tags niet gevonden',
                    'update-success' => 'Tags succesvol bijgewerkt',
                    'update-failed'  => 'Tag bijwerken mislukt',
                    'delete-success' => 'Asset-tags succesvol verwijderd',
                    'delete-failed'  => 'Asset Tags konden niet worden verwijderd',
                ],
                'delete-success'                          => 'Asset succesvol verwijderd',
                'delete-failed-due-to-attached-resources' => 'Asset is in gebruik. Ontkoppel voordat je verwijdert',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Massa verwijdering succesvol.',
                    'files-upload-success'                => 'Bestanden succesvol geüpload.',
                    'file-upload-success'                 => 'Bestand succesvol geüpload.',
                    'not-found'                           => 'Bestand niet gevonden',
                    'edit-success'                        => 'Bestand succesvol geüpload',
                    'show-success'                        => 'Bestand succesvol gevonden',
                    'update-success'                      => 'Bestand succesvol bijgewerkt',
                    'not-found-to-update'                 => 'Bestand bestaat niet',
                    'not-found-to-destroy'                => 'Bestand bestaat niet',
                    'files-upload-failed'                 => 'Bestanden konden niet worden geüpload.',
                    'file-upload-failed'                  => 'Bestand kon niet worden geüpload',
                    'invalid-file'                        => 'Ongeldig bestand opgegeven',
                    'invalid-file-format'                 => 'Ongeldig formaat',
                    'invalid-file-format-or-not-provided' => 'Geen bestanden opgegeven of ongeldig formaat.',
                    'download-image-failed'               => 'Afbeelding downloaden van URL mislukt',
                    'file-process-failed'                 => 'Sommige bestanden konden niet worden verwerkt',
                    'file-forbidden-type'                 => 'Bestand heeft een verboden type of extensie.',
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
            'property'         => 'Eigenschap',
            'comment'          => 'Opmerking',
            'linked_resources' => 'Gekoppelde Bronnen',
            'directory'        => 'Map',
            'tag'              => 'Tag',
            'create'           => 'Aanmaken',
            'edit'             => 'Bewerken',
            'update'           => 'Bijwerken',
            'delete'           => 'Verwijderen',
            'list'             => 'Lijst',
            'view'             => 'Bekijken',
            'upload'           => 'Uploaden',
            're_upload'        => 'Opnieuw Uploaden',
            'mass_update'      => 'Massa Bijwerken',
            'mass_delete'      => 'Massa Verwijderen',
            'download'         => 'Downloaden',
            'custom_download'  => 'Aangepaste Download',
            'rename'           => 'Hernoemen',
            'move'             => 'Verplaatsen',
            'copy'             => 'Kopiëren',
            'copy-structure'   => 'Kopieer Mapstructuur',
            'download-zip'     => 'Download Zip',
            'asset-assign'     => 'Wijs Asset toe',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Het veld :attribute is verplicht.',
            ],

            'comment' => [
                'required' => 'Het bericht van de Opmerking is verplicht.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Het Tag veld is verplicht.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Het veld Naam is verplicht.',
                    'unique'   => 'De Naam is al in gebruik.',
                ],
                'language' => [
                    'not-found' => 'De geselecteerde taal is niet gevonden of momenteel uitgeschakeld.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Deze actie is niet toegestaan.',
        ],
    ],
];
