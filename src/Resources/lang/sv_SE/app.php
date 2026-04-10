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
                    'message' => 'Om du tar bort den här katalogen tas även alla underkataloger i den bort. Den här åtgärden är permanent och kan inte ångras.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Lägg till tillgång',
                    'assign-assets' => 'Tilldela tillgångar',
                    'assign'        => 'Tilldela',
                    'preview-asset' => 'Förhandsgranska tillgång',
                    'preview'       => 'Förhandsgranska',
                    'remove'        => 'Ta bort',
                    'download'      => 'Ladda ner',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Filnamn',
                    'tags'           => 'Taggar',
                    'property-name'  => 'Egenskapsnamn',
                    'property-value' => 'Egenskapsvärde',
                    'created-at'     => 'Skapad',
                    'updated-at'     => 'Uppdaterad',
                    'extension'      => 'Filtillägg',
                    'path'           => 'Sökväg',
                    'size'           => 'Storlek',
                ],

                'directory' => [
                    'title'        => 'Katalog',
                    'create'       => [
                        'title'    => 'Skapa katalog',
                        'name'     => 'Namn',
                        'save-btn' => 'Spara katalog',
                    ],

                    'rename' => [
                        'title' => 'Byt namn på katalog',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Byt namn på tillgång',
                            'save-btn' => 'Spara tillgång',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Ta bort',
                        'rename'                    => 'Byt namn',
                        'copy'                      => 'Kopiera',
                        'download'                  => 'Ladda ner',
                        'download-zip'              => 'Ladda ner zip',
                        'paste'                     => 'Klistra in',
                        'add-directory'             => 'Lägg till katalog',
                        'upload-files'              => 'Ladda upp filer',
                        'copy-directory-structured' => 'Kopiera katalogstruktur',
                        'get-by-id'                 => 'Hämta efter ID',
                        'comment'                   => 'Kommentar',
                    ],

                    'linked-resources'                          => 'Länkade resurser',
                    'not-found'                                 => 'Ingen katalog hittades',
                    'created-success'                           => 'Katalog skapad',
                    'updated-success'                           => 'Katalog uppdaterad',
                    'moved-success'                             => 'Katalog flyttad',
                    'fetch-all-success'                         => 'Kataloger hämtade',
                    'can-not-deleted'                           => 'Katalogen kan inte tas bort eftersom det är rotkatalogen.',
                    'deleting-in-progress'                      => 'Borttagning av katalog pågår',
                    'can-not-copy'                              => 'Katalogen kan inte kopieras eftersom det är rotkatalogen.',
                    'coping-in-progress'                        => 'Kopiering av katalogstruktur pågår.',
                    'asset-not-found'                           => 'Ingen tillgång hittades',
                    'asset-renamed-success'                     => 'Tillgången har bytt namn',
                    'asset-moved-success'                       => 'Tillgången har flyttats',
                    'asset-name-already-exist'                  => 'Det nya namnet finns redan på en annan tillgång vid namn :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Tillgångens namn står i konflikt med en befintlig fil i samma katalog.',
                    'old-file-not-found'                        => 'Filen som efterfrågades på sökvägen :old_path hittades inte.',
                    'image-name-is-the-same'                    => 'Det här namnet finns redan. Ange ett annat.',
                    'not-writable'                              => 'Du har inte behörighet att :actionType en :type på den här platsen ":path".',
                    'empty-directory'                           => 'Den här katalogen är tom.',
                    'failed-download-directory'                 => 'Det gick inte att skapa zip-filen.',
                    'not-allowed'                               => 'Uppladdning av skriptfiler är inte tillåten.',
                ],

                'title'       => 'DAM',
                'description' => 'Verktyget hjälper dig att organisera, lagra och hantera alla dina mediatillgångar på ett och samma ställe',
                'root'        => 'Rot',
                'upload'      => 'Ladda upp',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Tillgångsegenskaper',
                        'create-btn' => 'Skapa egenskap',

                        'datagrid'      => [
                            'name'     => 'Namn',
                            'type'     => 'Typ',
                            'language' => 'Språk',
                            'value'    => 'Värde',
                            'edit'     => 'Redigera',
                            'delete'   => 'Ta bort',
                        ],

                        'create'     => [
                            'title'    => 'Skapa egenskap',
                            'name'     => 'Namn',
                            'type'     => 'Typ',
                            'language' => 'Språk',
                            'value'    => 'Värde',
                            'save-btn' => 'Spara',
                        ],
                        'edit' => [
                            'title' => 'Redigera egenskap',
                        ],
                        'delete-success' => 'Tillgångsegenskap borttagen',
                        'create-success' => 'Tillgångsegenskap skapad',
                        'update-success' => 'Tillgångsegenskap uppdaterad',
                        'not-found'      => 'Egenskap hittades inte',
                        'found-success'  => 'Egenskap hittades',
                    ],
                ],
                'comments' => [
                    'index'  => 'Lägg till kommentar',
                    'create' => [
                        'create-success' => 'Kommentaren har lagts till',
                        'create-failure' => 'Det gick inte att skapa kommentaren',
                    ],
                    'post-comment'    => 'Skicka kommentar',
                    'post-reply'      => 'Skicka svar',
                    'reply'           => 'Svara',
                    'add-reply'       => 'Lägg till svar',
                    'add-comment'     => 'Lägg till kommentar',
                    'no-comments'     => 'Inga kommentarer ännu',
                    'not-found'       => 'Kommentarer hittades inte',
                    'updated-success' => 'Kommentar uppdaterad',
                    'update-failed'   => 'Det gick inte att uppdatera kommentaren',
                    'delete-success'  => 'Tillgångskommentar borttagen',
                    'delete-failed'   => 'Det gick inte att ta bort tillgångskommentaren',
                ],
                'edit' => [
                    'title'              => 'Redigera tillgång',
                    'name'               => 'Namn',
                    'value'              => 'Värde',
                    'back-btn'           => 'Tillbaka',
                    'save-btn'           => 'Spara',
                    'embedded_meta_info' => 'Inbäddad metainformation',
                    'custom_meta_info'   => 'Anpassad metainformation',
                    'tags'               => 'Taggar',
                    'select-tags'        => 'Välj eller skapa en tagg',
                    'tag'                => 'Tagg',
                    'directory-path'     => 'Katalogsökväg',
                    'add_tags'           => 'Lägg till taggar',
                    'tab'                => [
                        'preview'          => 'Förhandsgranskning',
                        'properties'       => 'Egenskaper',
                        'comments'         => 'Kommentarer',
                        'linked_resources' => 'Länkade resurser',
                        'history'          => 'Historik',
                    ],
                    'button' => [
                        'download'        => 'Ladda ner',
                        'custom_download' => 'Anpassad nedladdning',
                        'rename'          => 'Byt namn',
                        're_upload'       => 'Ladda upp igen',
                        'delete'          => 'Ta bort',
                    ],

                    'custom-download' => [
                        'title'              => 'Anpassad nedladdning',
                        'format'             => 'Format',
                        'width'              => 'Bredd (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Höjd (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Ladda ner',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Taggen finns redan',
                    'image-source-not-readable' => 'Bildkällan kan inte läsas',
                    'failed-to-read'            => 'Det gick inte att läsa bildens metadata :exception',
                    'file-re-upload-success'    => 'Filerna har laddats upp igen.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategori',
                            'product-sku'   => 'Produkt-Sku: ',
                            'category code' => 'Kategorikod: ',
                            'resource-type' => 'Resurstyp',
                            'resource'      => 'Resurs',
                            'resource-view' => 'Resursvy',
                        ],
                    ],
                    'found-success' => 'Resurs hittades',
                    'not-found'     => 'Resurs hittades inte',
                ],
                'tags' => [
                    'index'  => 'Lägg till taggar',
                    'create' => [
                        'create-success' => 'Taggarna har lagts till',
                        'create-failure' => 'Det gick inte att skapa taggarna',
                    ],

                    'no-comments'    => 'Inga taggar ännu',
                    'found-success'  => 'Tagg hittades',
                    'not-found'      => 'Taggar hittades inte',
                    'update-success' => 'Taggar uppdaterade',
                    'update-failed'  => 'Det gick inte att uppdatera taggarna',
                    'delete-success' => 'Tillgångstaggar borttagna',
                    'delete-failed'  => 'Det gick inte att ta bort tillgångstaggarna',
                ],
                'delete-success'                          => 'Tillgång borttagen',
                'delete-failed-due-to-attached-resources' => 'Tillgången används. Koppla bort innan du tar bort',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Massborttagning genomförd.',
                    'files-upload-success'                => 'Filer uppladdade.',
                    'file-upload-success'                 => 'Fil uppladdad.',
                    'not-found'                           => 'Fil hittades inte',
                    'edit-success'                        => 'Fil uppladdad',
                    'show-success'                        => 'Fil hittades',
                    'update-success'                      => 'Fil uppdaterad',
                    'not-found-to-update'                 => 'Filen finns inte',
                    'not-found-to-destroy'                => 'Filen finns inte',
                    'files-upload-failed'                 => 'Det gick inte att ladda upp filerna.',
                    'file-upload-failed'                  => 'Det gick inte att ladda upp filen',
                    'invalid-file'                        => 'Ogiltig fil angiven',
                    'invalid-file-format'                 => 'Ogiltigt format',
                    'invalid-file-format-or-not-provided' => 'Inga filer angavs eller ogiltigt format.',
                    'download-image-failed'               => 'Det gick inte att ladda ner bilden från URL',
                    'file-process-failed'                 => 'Vissa filer kunde inte bearbetas',
                    'file-forbidden-type'                 => 'Filen har en förbjuden typ eller filtillägg.',
                    'file-too-large'                      => 'Filen är för stor. Den maximalt tillåtna storleken är :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Tillgång',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Tillgång',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Tillgång',
            'property'         => 'Egenskap',
            'comment'          => 'Kommentar',
            'linked_resources' => 'Länkade resurser',
            'directory'        => 'Katalog',
            'tag'              => 'Tagg',
            'create'           => 'Skapa',
            'edit'             => 'Redigera',
            'update'           => 'Uppdatera',
            'delete'           => 'Ta bort',
            'list'             => 'Lista',
            'view'             => 'Visa',
            'upload'           => 'Ladda upp',
            're_upload'        => 'Ladda upp igen',
            'mass_update'      => 'Massuppdatering',
            'mass_delete'      => 'Massborttagning',
            'download'         => 'Ladda ner',
            'custom_download'  => 'Anpassad nedladdning',
            'rename'           => 'Byt namn',
            'move'             => 'Flytta',
            'copy'             => 'Kopiera',
            'copy-structure'   => 'Kopiera katalogstruktur',
            'download-zip'     => 'Ladda ner zip',
            'asset-assign'     => 'Tilldela tillgång',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Fältet :attribute är obligatoriskt.',
            ],

            'comment' => [
                'required' => 'Kommentarmeddelandet är obligatoriskt.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Tagg-fältet är obligatoriskt.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Namnfältet är obligatoriskt.',
                    'unique'   => 'Namnet är redan taget.',
                ],
                'language' => [
                    'not-found' => 'Det valda språket kunde inte hittas eller är för närvarande inaktiverat.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Den här åtgärden är inte auktoriserad.',
        ],
    ],
];
