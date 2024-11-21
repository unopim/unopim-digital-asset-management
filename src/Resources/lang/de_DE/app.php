<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'DAMM', // Consider a more descriptive translation like "Digital Asset Management"
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'Das Löschen dieses Verzeichnisses löscht auch alle Unterverzeichnisse darin. Dieser Vorgang ist dauerhaft und kann nicht rückgängig gemacht werden.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Asset hinzufügen',
                    'assign-assets' => 'Assets zuweisen',
                    'assign'        => 'Zuweisen',
                    'preview-asset' => 'Asset-Vorschau',
                    'preview'       => 'Vorschau',
                    'remove'        => 'Entfernen',
                    'download'      => 'Herunterladen',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAMM', // Consider a more descriptive translation

                'datagrid' => [
                    'file-name'      => 'Dateiname',
                    'tags'           => 'Tags', // or "Schlagwörter"
                    'property-name'  => 'Eigenschaftsname',
                    'property-value' => 'Eigenschaftswert',
                    'created-at'     => 'Erstellt am',
                    'updated-at'     => 'Aktualisiert am',
                    'extension'      => 'Erweiterung',
                    'path'           => 'Pfad',
                ],

                'directory' => [
                    'title'        => 'Verzeichnis',
                    'create'       => [
                        'title'    => 'Verzeichnis erstellen',
                        'name'     => 'Name',
                        'save-btn' => 'Verzeichnis speichern',
                    ],

                    'rename' => [
                        'title' => 'Verzeichnis umbenennen',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Asset umbenennen',
                            'save-btn' => 'Asset speichern',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Löschen',
                        'rename'                    => 'Umbenennen',
                        'copy'                      => 'Kopieren',
                        'download'                  => 'Herunterladen',
                        'download-zip'              => 'ZIP herunterladen',
                        'paste'                     => 'Einfügen',
                        'add-directory'             => 'Verzeichnis hinzufügen',
                        'upload-files'              => 'Dateien hochladen',
                        'copy-directory-structured' => 'Verzeichnisstruktur kopieren',
                    ],

                    'not-found'                                 => 'Kein Verzeichnis gefunden',
                    'created-success'                           => 'Verzeichnis erfolgreich erstellt',
                    'updated-success'                           => 'Verzeichnis erfolgreich aktualisiert',
                    'moved-success'                             => 'Verzeichnis erfolgreich verschoben',
                    'can-not-deleted'                           => 'Das Verzeichnis kann nicht gelöscht werden, da es das Stammverzeichnis ist.',
                    'deleting-in-progress'                      => 'Verzeichnis wird gelöscht',
                    'can-not-copy'                              => 'Das Verzeichnis kann nicht kopiert werden, da es das Stammverzeichnis ist.',
                    'coping-in-progress'                        => 'Verzeichnisstruktur wird kopiert',
                    'asset-not-found'                           => 'Kein Asset gefunden',
                    'asset-renamed-success'                     => 'Asset erfolgreich umbenannt',
                    'asset-moved-success'                       => 'Asset erfolgreich verschoben',
                    'asset-name-already-exist'                  => 'Der neue Name existiert bereits mit einem anderen Asset namens :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Der Asset-Name kollidiert mit einer vorhandenen Datei im selben Verzeichnis.',
                    'old-file-not-found'                        => 'Die angeforderte Datei unter dem Pfad :old_path wurde nicht gefunden.',
                    'image-name-is-the-same'                    => 'Dieser Name existiert bereits. Bitte geben Sie einen anderen ein.',
                    'not-writable'                              => 'Sie sind nicht berechtigt, :type an diesem Speicherort ":path" zu :actionType.',
                    'empty-directory'                           => 'Dieses Verzeichnis ist leer.',
                    'failed-download-directory'                 => 'Fehler beim Erstellen der ZIP-Datei.',

                ],

                'title'       => 'DAMM',  // Consider a more descriptive translation
                'description' => 'Mit diesem Tool können Sie alle Ihre Medieninhalte an einem Ort organisieren, speichern und verwalten.',
                'root'        => 'Stammverzeichnis',
                'upload'      => 'Hochladen',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Asset-Eigenschaften',
                        'create-btn' => 'Eigenschaft erstellen',

                        'datagrid' => [
                            'name'     => 'Name',
                            'type'     => 'Typ',
                            'language' => 'Sprache',
                            'value'    => 'Wert',
                            'edit'     => 'Bearbeiten',
                            'delete'   => 'Löschen',
                        ],

                        'create' => [
                            'title'    => 'Eigenschaft erstellen',
                            'name'     => 'Name',
                            'type'     => 'Typ',
                            'language' => 'Sprache',
                            'value'    => 'Wert',
                            'save-btn' => 'Speichern',
                        ],
                        'edit' => [
                            'title' => 'Eigenschaft bearbeiten',
                        ],
                        'delete-success' => 'Asset-Eigenschaft erfolgreich gelöscht',
                        'create-success' => 'Asset-Eigenschaft erfolgreich erstellt',
                        'update-success' => 'Asset-Eigenschaft erfolgreich aktualisiert',
                    ],
                ],
                'comments' => [
                    'index'        => 'Kommentar hinzufügen',
                    'create'       => [
                        'create-success' => 'Kommentar wurde erfolgreich hinzugefügt',
                    ],
                    'post-comment' => 'Kommentar posten',
                    'post-reply'   => 'Antwort posten',
                    'reply'        => 'Antworten',
                    'add-reply'    => 'Antwort hinzufügen',
                    'add-comment'  => 'Kommentar hinzufügen',
                    'no-comments'  => 'Noch keine Kommentare',

                ],
                'edit' => [
                    'title'              => 'Asset bearbeiten',
                    'name'               => 'Name',
                    'value'              => 'Wert',
                    'back-btn'           => 'Zurück',
                    'save-btn'           => 'Speichern',
                    'embedded_meta_info' => 'Eingebettete Metadaten',
                    'custom_meta_info'   => 'Benutzerdefinierte Metadaten',
                    'tags'               => 'Tags', // or "Schlagwörter"
                    'select-tags'        => 'Tag auswählen oder erstellen',  // or "Schlagwort"
                    'tag'                => 'Tag',  // or "Schlagwort"
                    'directory-path'     => 'Verzeichnispfad',
                    'add_tags'           => 'Tags hinzufügen',  // or "Schlagwörter"
                    'tab'                => [
                        'preview'           => 'Vorschau',
                        'properties'        => 'Eigenschaften',
                        'comments'          => 'Kommentare',
                        'linked_resources'  => 'Verknüpfte Ressourcen',
                        'history'           => 'Verlauf',
                        'metadata'          => 'Metadaten', // Added metadata tab
                    ],
                    'button' => [
                        'download'        => 'Herunterladen',
                        'custom_download' => 'Benutzerdefinierter Download',
                        'rename'          => 'Umbenennen',
                        're_upload'       => 'Erneut hochladen',
                        'delete'          => 'Löschen',
                    ],

                    'custom-download' => [
                        'title'              => 'Benutzerdefinierter Download',
                        'format'             => 'Format',
                        'width'              => 'Breite (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Höhe (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Herunterladen',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Tag existiert bereits',
                    'image-source-not-readable' => 'Bildquelle nicht lesbar',
                    'failed-to-read'            => 'Fehler beim Lesen der Bildmetadaten :exception',
                    'file_re_upload_success'    => 'Dateien erfolgreich erneut hochgeladen.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategorie',
                            'product-sku'   => 'Produkt-SKU: ',
                            'category code' => 'Kategoriecode: ',
                            'resource-type' => 'Ressourcentyp',
                            'resource'      => 'Ressource',
                            'resource-view' => 'Ressourcenansicht',
                        ],
                    ],
                ],
                'delete-success'                          => 'Asset erfolgreich gelöscht',
                'delete-failed-due-to-attached-resources' => 'Asset konnte nicht gelöscht werden, da es mit Ressourcen verknüpft ist (Asset-Name: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Massenlöschung erfolgreich.',
                    'files_upload_success' => 'Dateien erfolgreich hochgeladen.',
                    'file_upload_success'  => 'Datei erfolgreich hochgeladen.',
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
            'menu'             => 'DAMM',  // Consider a more descriptive translation
            'asset'            => 'Asset',
            'property'         => 'Eigenschaft',
            'comment'          => 'Kommentar',
            'linked_resources' => 'Verknüpfte Ressourcen',
            'directory'        => 'Verzeichnis',
            'tag'              => 'Tag', // or "Schlagwort"
            'create'           => 'Erstellen',
            'edit'             => 'Bearbeiten',
            'update'           => 'Aktualisieren',
            'delete'           => 'Löschen',
            'list'             => 'Liste',
            'view'             => 'Anzeigen',
            'upload'           => 'Hochladen',
            're_upload'        => 'Erneut hochladen',
            'mass_update'      => 'Massenaktualisierung',
            'mass_delete'      => 'Massenlöschung',
            'download'         => 'Herunterladen',
            'custom_download'  => 'Benutzerdefinierter Download',
            'rename'           => 'Umbenennen',
            'move'             => 'Verschieben',
            'copy'             => 'Kopieren',
            'copy-structure'   => 'Verzeichnisstruktur kopieren',
            'download-zip'     => 'ZIP herunterladen',
            'asset-assign'     => 'Asset zuweisen',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Das Feld :attribute ist erforderlich.',
            ],

            'comment' => [
                'required' => 'Die Kommentarnachricht ist erforderlich.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Das Feld "Name" ist erforderlich.',
                    'unique'   => 'Der Name ist bereits vergeben.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Diese Aktion ist nicht autorisiert.',
        ],
    ],
];
