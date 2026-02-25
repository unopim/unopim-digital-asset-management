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
                    'message' => 'Wenn Sie dieses Verzeichnis löschen, werden auch alle Unterverzeichnisse darin gelöscht. Diese Aktion ist dauerhaft und kann nicht rückgängig gemacht werden.',
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
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Dateiname',
                    'tags'           => 'Tags',
                    'property-name'  => 'Eigenschaftsname',
                    'property-value' => 'Eigenschaftswert',
                    'created-at'     => 'Erstellt am',
                    'updated-at'     => 'Aktualisiert am',
                    'extension'      => 'Erweiterung',
                    'path'           => 'Pfad',
                    'size'           => 'Größe',
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
                    'fetch-all-success'                         => 'Verzeichnisse erfolgreich abgerufen',
                    'can-not-deleted'                           => 'Verzeichnis kann nicht gelöscht werden, da es das Root-Verzeichnis ist.',
                    'deleting-in-progress'                      => 'Verzeichnis wird gelöscht',
                    'can-not-copy'                              => 'Verzeichnis kann nicht kopiert werden, da es das Root-Verzeichnis ist.',
                    'coping-in-progress'                        => 'Verzeichnisstruktur wird kopiert.',
                    'asset-not-found'                           => 'Kein Asset gefunden',
                    'asset-renamed-success'                     => 'Asset erfolgreich umbenannt',
                    'asset-moved-success'                       => 'Asset erfolgreich verschoben',
                    'asset-name-already-exist'                  => 'Der neue Name existiert bereits für ein anderes Asset namens :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Der Asset-Name steht im Konflikt mit einer bestehenden Datei im selben Verzeichnis.',
                    'old-file-not-found'                        => 'Die Datei unter dem Pfad :old_path wurde nicht gefunden.',
                    'image-name-is-the-same'                    => 'Dieser Name existiert bereits. Bitte geben Sie einen anderen ein.',
                    'not-writable'                              => 'Sie dürfen eine :type an diesem Speicherort ":path" nicht :actionType.',
                    'empty-directory'                           => 'Dieses Verzeichnis ist leer.',
                    'failed-download-directory'                 => 'Fehler beim Erstellen der ZIP-Datei.',
                    'not-allowed'                               => 'Das Hochladen von Skriptdateien ist nicht erlaubt.',
                ],

                'title'       => 'DAM',
                'description' => 'Dieses Tool hilft Ihnen, alle Ihre Medienassets an einem Ort zu organisieren, zu speichern und zu verwalten.',
                'root'        => 'Root',
                'upload'      => 'Hochladen',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Asset-Eigenschaften',
                        'create-btn' => 'Eigenschaft erstellen',

                        'datagrid'      => [
                            'name'     => 'Name',
                            'type'     => 'Typ',
                            'language' => 'Sprache',
                            'value'    => 'Wert',
                            'edit'     => 'Bearbeiten',
                            'delete'   => 'Löschen',
                        ],

                        'create'     => [
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
                        'not-found'      => 'Eigenschaft nicht gefunden',
                        'found-success'  => 'Eigenschaft erfolgreich gefunden',

                    ],
                ],
                'comments' => [
                    'index'  => 'Kommentar hinzufügen',
                    'create' => [
                        'create-success' => 'Kommentar wurde erfolgreich hinzugefügt',
                        'create-failure' => 'Kommentar konnte nicht erstellt werden',
                    ],
                    'post-comment'    => 'Kommentar posten',
                    'post-reply'      => 'Antwort posten',
                    'reply'           => 'Antwort',
                    'add-reply'       => 'Antwort hinzufügen',
                    'add-comment'     => 'Kommentar hinzufügen',
                    'no-comments'     => 'Noch keine Kommentare',
                    'not-found'       => 'Kommentare nicht gefunden',
                    'updated-success' => 'Kommentar erfolgreich aktualisiert',
                    'update-failed'   => 'Kommentar konnte nicht aktualisiert werden',
                    'delete-success'  => 'Asset-Tags wurden erfolgreich entfernt',
                    'delete-failed'   => 'Asset-Kommentar konnte nicht gelöscht werden',
                ],
                'edit' => [
                    'title'              => 'Asset bearbeiten',
                    'name'               => 'Name',
                    'value'              => 'Wert',
                    'back-btn'           => 'Zurück',
                    'save-btn'           => 'Speichern',
                    'embedded_meta_info' => 'Eingebettete Metadaten',
                    'custom_meta_info'   => 'Benutzerdefinierte Metadaten',
                    'tags'               => 'Tags',
                    'select-tags'        => 'Tag wählen oder erstellen',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Verzeichnispfad',
                    'add_tags'           => 'Tags hinzufügen',
                    'tab'                => [
                        'preview'          => 'Vorschau',
                        'properties'       => 'Eigenschaften',
                        'comments'         => 'Kommentare',
                        'linked_resources' => 'Verknüpfte Ressourcen',
                        'history'          => 'Verlauf',
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
                    'failed-to-read'            => 'Metadaten des Bildes konnten nicht gelesen werden: :exception',
                    'file-re-upload-success'    => 'Dateien wurden erfolgreich erneut hochgeladen.',
                ],

                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategorie',
                            'product-sku'   => 'Produkt-SKU: ',
                            'category code' => 'Kategorie-Code: ',
                            'resource-type' => 'Ressourcentyp',
                            'resource'      => 'Ressource',
                            'resource-view' => 'Ressourcenansicht',
                        ],
                    ],
                    'found-success' => 'Ressource erfolgreich gefunden',
                    'not-found'     => 'Ressource nicht gefunden',
                ],

                'tags' => [
                    'index'  => 'Tags hinzufügen',
                    'create' => [
                        'create-success' => 'Tags wurden erfolgreich hinzugefügt',
                        'create-failure' => 'Fehler beim Erstellen der Tags',
                    ],
                    'no-comments'    => 'Noch keine Tags',
                    'found-success'  => 'Tag erfolgreich gefunden',
                    'not-found'      => 'Tags nicht gefunden',
                    'update-success' => 'Tags erfolgreich aktualisiert',
                    'update-failed'  => 'Tags konnten nicht aktualisiert werden',
                    'delete-success' => 'Asset-Tags erfolgreich gelöscht',
                    'delete-failed'  => 'Asset-Tags konnten nicht gelöscht werden',
                ],

                'delete-success'                          => 'Asset erfolgreich gelöscht',
                'delete-failed-due-to-attached-resources' => 'Asset wird verwendet. Vor dem Löschen bitte trennen',
                'datagrid' => [
                    'mass-delete-success'                 => 'Massenlöschung erfolgreich.',
                    'files-upload-success'                => 'Dateien erfolgreich hochgeladen.',
                    'file-upload-success'                 => 'Datei erfolgreich hochgeladen.',
                    'not-found'                           => 'Datei nicht gefunden',
                    'edit-success'                        => 'Datei erfolgreich hochgeladen',
                    'show-success'                        => 'Datei erfolgreich gefunden',
                    'update-success'                      => 'Datei erfolgreich aktualisiert',
                    'not-found-to-update'                 => 'Datei existiert nicht',
                    'not-found-to-destroy'                => 'Datei existiert nicht',
                    'files-upload-failed'                 => 'Fehler beim Hochladen der Dateien.',
                    'file-upload-failed'                  => 'Datei konnte nicht hochgeladen werden',
                    'invalid-file'                        => 'Ungültige Datei bereitgestellt',
                    'invalid-file-format'                 => 'Ungültiges Format',
                    'invali-file-format-or-not-provided'  => 'Keine Datei bereitgestellt oder ungültiges Format.',
                    'download-image-failed'               => 'Fehler beim Herunterladen des Bildes von der URL',
                    'file-process-failed'                 => 'Einige Dateien konnten nicht verarbeitet werden',
                    'file-forbidden-type'                 => 'Die Datei hat einen verbotenen Typ oder eine verbotene Erweiterung.',
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
            'property'         => 'Eigenschaft',
            'comment'          => 'Kommentar',
            'linked_resources' => 'Verknüpfte Ressourcen',
            'directory'        => 'Verzeichnis',
            'tag'              => 'Tag',
            'create'           => 'Erstellen',
            'edit'             => 'Bearbeiten',
            'update'           => 'Aktualisieren',
            'delete'           => 'Löschen',
            'list'             => 'Auflisten',
            'view'             => 'Ansehen',
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
                'required' => 'Das Kommentarfeld ist erforderlich.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Das Tag-Feld ist erforderlich.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Das Namensfeld ist erforderlich.',
                    'unique'   => 'Der Name wurde bereits vergeben.',
                ],
                'language' => [
                    'not-found' => 'Die ausgewählte Sprache wurde nicht gefunden oder ist derzeit deaktiviert.',
                ],
            ],
        ],
        'errors' => [
            '401' => 'Diese Aktion ist nicht autorisiert.',
        ],
    ],
];
