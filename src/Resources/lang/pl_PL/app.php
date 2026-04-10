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
                    'message' => 'Usunięcie tego katalogu spowoduje również usunięcie wszystkich podkatalogów w jego wnętrzu. Ta operacja jest trwała i nie można jej cofnąć.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Dodaj zasób',
                    'assign-assets' => 'Przypisz zasoby',
                    'assign'        => 'Przypisz',
                    'preview-asset' => 'Podgląd zasobu',
                    'preview'       => 'Podgląd',
                    'remove'        => 'Usuń',
                    'download'      => 'Pobierz',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nazwa pliku',
                    'tags'           => 'Tagi',
                    'property-name'  => 'Nazwa właściwości',
                    'property-value' => 'Wartość właściwości',
                    'created-at'     => 'Data utworzenia',
                    'updated-at'     => 'Data aktualizacji',
                    'extension'      => 'Rozszerzenie',
                    'path'           => 'Ścieżka',
                    'size'           => 'Rozmiar',
                ],

                'directory' => [
                    'title'  => 'Katalog',
                    'create' => [
                        'title'    => 'Utwórz katalog',
                        'name'     => 'Nazwa',
                        'save-btn' => 'Zapisz katalog',
                    ],

                    'rename' => [
                        'title' => 'Zmień nazwę katalogu',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Zmień nazwę zasobu',
                            'save-btn' => 'Zapisz zasób',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Usuń',
                        'rename'                    => 'Zmień nazwę',
                        'copy'                      => 'Kopiuj',
                        'download'                  => 'Pobierz',
                        'download-zip'              => 'Pobierz Zip',
                        'paste'                     => 'Wklej',
                        'add-directory'             => 'Dodaj katalog',
                        'upload-files'              => 'Prześlij pliki',
                        'copy-directory-structured' => 'Kopiuj strukturę katalogu',
                        'get-by-id'                 => 'Pobierz według ID',
                        'comment'                   => 'Komentarz',
                    ],

                    'linked-resources'                          => 'Powiązane zasoby',
                    'not-found'                                 => 'Nie znaleziono katalogu',
                    'created-success'                           => 'Katalog został pomyślnie utworzony',
                    'updated-success'                           => 'Katalog został pomyślnie zaktualizowany',
                    'moved-success'                             => 'Katalog został pomyślnie przeniesiony',
                    'fetch-all-success'                         => 'Katalogi zostały pomyślnie pobrane',
                    'can-not-deleted'                           => 'Katalogu nie można usunąć, ponieważ jest katalogiem głównym.',
                    'deleting-in-progress'                      => 'Usuwanie katalogu w toku',
                    'can-not-copy'                              => 'Katalogu nie można skopiować, ponieważ jest katalogiem głównym.',
                    'coping-in-progress'                        => 'Kopiowanie struktury katalogu w toku.',
                    'asset-not-found'                           => 'Nie znaleziono zasobu',
                    'asset-renamed-success'                     => 'Nazwa zasobu została pomyślnie zmieniona',
                    'asset-moved-success'                       => 'Zasób został pomyślnie przeniesiony',
                    'asset-name-already-exist'                  => 'Nowa nazwa już istnieje dla innego zasobu o nazwie :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Nazwa zasobu jest sprzeczna z istniejącym plikiem w tym samym katalogu.',
                    'old-file-not-found'                        => 'Plik żądany pod ścieżką :old_path nie został znaleziony.',
                    'image-name-is-the-same'                    => 'Ta nazwa już istnieje. Proszę wprowadzić inną.',
                    'not-writable'                              => 'Nie masz uprawnień do :actionType :type w tej lokalizacji ":path".',
                    'empty-directory'                           => 'Ten katalog jest pusty.',
                    'failed-download-directory'                 => 'Nie udało się utworzyć pliku zip.',
                    'not-allowed'                               => 'Przesyłanie plików skryptów jest niedozwolone.',
                ],

                'title'       => 'DAM',
                'description' => 'Narzędzie pomoże Ci uporządkować, przechowywać i zarządzać wszystkimi zasobami medialnymi w jednym miejscu',
                'root'        => 'Katalog główny',
                'upload'      => 'Prześlij',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Właściwości zasobu',
                        'create-btn' => 'Utwórz właściwość',

                        'datagrid' => [
                            'name'     => 'Nazwa',
                            'type'     => 'Typ',
                            'language' => 'Język',
                            'value'    => 'Wartość',
                            'edit'     => 'Edytuj',
                            'delete'   => 'Usuń',
                        ],

                        'create' => [
                            'title'    => 'Utwórz właściwość',
                            'name'     => 'Nazwa',
                            'type'     => 'Typ',
                            'language' => 'Język',
                            'value'    => 'Wartość',
                            'save-btn' => 'Zapisz',
                        ],
                        'edit' => [
                            'title' => 'Edytuj właściwość',
                        ],
                        'delete-success' => 'Właściwość zasobu została pomyślnie usunięta',
                        'create-success' => 'Właściwość zasobu została pomyślnie utworzona',
                        'update-success' => 'Właściwość zasobu została pomyślnie zaktualizowana',
                        'not-found'      => 'Nie znaleziono właściwości',
                        'found-success'  => 'Właściwość została pomyślnie znaleziona',
                    ],
                ],
                'comments' => [
                    'index'  => 'Dodaj komentarz',
                    'create' => [
                        'create-success' => 'Komentarz został pomyślnie dodany',
                        'create-failure' => 'Nie udało się utworzyć komentarza',
                    ],
                    'post-comment'    => 'Opublikuj komentarz',
                    'post-reply'      => 'Opublikuj odpowiedź',
                    'reply'           => 'Odpowiedz',
                    'add-reply'       => 'Dodaj odpowiedź',
                    'add-comment'     => 'Dodaj komentarz',
                    'no-comments'     => 'Brak komentarzy',
                    'not-found'       => 'Nie znaleziono komentarzy',
                    'updated-success' => 'Komentarz został pomyślnie zaktualizowany',
                    'update-failed'   => 'Nie udało się zaktualizować komentarza',
                    'delete-success'  => 'Komentarz zasobu został pomyślnie usunięty',
                    'delete-failed'   => 'Nie udało się usunąć komentarza zasobu',
                ],
                'edit' => [
                    'title'              => 'Edytuj zasób',
                    'name'               => 'Nazwa',
                    'value'              => 'Wartość',
                    'back-btn'           => 'Wstecz',
                    'save-btn'           => 'Zapisz',
                    'embedded_meta_info' => 'Wbudowane informacje meta',
                    'custom_meta_info'   => 'Niestandardowe informacje meta',
                    'tags'               => 'Tagi',
                    'select-tags'        => 'Wybierz lub utwórz tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Ścieżka katalogu',
                    'add_tags'           => 'Dodaj tagi',
                    'tab'                => [
                        'preview'          => 'Podgląd',
                        'properties'       => 'Właściwości',
                        'comments'         => 'Komentarze',
                        'linked_resources' => 'Powiązane zasoby',
                        'history'          => 'Historia',
                    ],
                    'button' => [
                        'download'        => 'Pobierz',
                        'custom_download' => 'Niestandardowe pobieranie',
                        'rename'          => 'Zmień nazwę',
                        're_upload'       => 'Prześlij ponownie',
                        'delete'          => 'Usuń',
                    ],

                    'custom-download' => [
                        'title'              => 'Niestandardowe pobieranie',
                        'format'             => 'Format',
                        'width'              => 'Szerokość (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Wysokość (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Pobierz',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Oryginał',
                        ],
                    ],

                    'tag-already-exists'        => 'Tag już istnieje',
                    'image-source-not-readable' => 'Źródło obrazu jest nieczytelne',
                    'failed-to-read'            => 'Nie udało się odczytać metadanych obrazu :exception',
                    'file-re-upload-success'    => 'Pliki zostały pomyślnie przesłane ponownie.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produkt',
                            'category'      => 'Kategoria',
                            'product-sku'   => 'Sku produktu: ',
                            'category code' => 'Kod kategorii: ',
                            'resource-type' => 'Typ zasobu',
                            'resource'      => 'Zasób',
                            'resource-view' => 'Widok zasobu',
                        ],
                    ],
                    'found-success' => 'Zasób został pomyślnie znaleziony',
                    'not-found'     => 'Nie znaleziono zasobu',
                ],
                'tags' => [
                    'index'  => 'Dodaj tagi',
                    'create' => [
                        'create-success' => 'Tagi zostały pomyślnie dodane',
                        'create-failure' => 'Nie udało się utworzyć tagów',
                    ],

                    'no-comments'    => 'Brak tagów',
                    'found-success'  => 'Tag został pomyślnie znaleziony',
                    'not-found'      => 'Nie znaleziono tagów',
                    'update-success' => 'Tagi zostały pomyślnie zaktualizowane',
                    'update-failed'  => 'Nie udało się zaktualizować tagów',
                    'delete-success' => 'Tagi zasobu zostały pomyślnie usunięte',
                    'delete-failed'  => 'Nie udało się usunąć tagów zasobu',
                ],
                'delete-success'                          => 'Zasób został pomyślnie usunięty',
                'delete-failed-due-to-attached-resources' => 'Zasób jest w użyciu. Odłącz przed usunięciem',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Masowe usuwanie zakończone pomyślnie.',
                    'files-upload-success'                => 'Pliki zostały pomyślnie przesłane.',
                    'file-upload-success'                 => 'Plik został pomyślnie przesłany.',
                    'not-found'                           => 'Nie znaleziono pliku',
                    'edit-success'                        => 'Plik został pomyślnie przesłany',
                    'show-success'                        => 'Plik został pomyślnie znaleziony',
                    'update-success'                      => 'Plik został pomyślnie zaktualizowany',
                    'not-found-to-update'                 => 'Plik nie istnieje',
                    'not-found-to-destroy'                => 'Plik nie istnieje',
                    'files-upload-failed'                 => 'Nie udało się przesłać plików.',
                    'file-upload-failed'                  => 'Nie udało się przesłać pliku',
                    'invalid-file'                        => 'Dostarczono nieprawidłowy plik',
                    'invalid-file-format'                 => 'Nieprawidłowy format',
                    'invalid-file-format-or-not-provided' => 'Nie dostarczono plików lub nieprawidłowy format.',
                    'download-image-failed'               => 'Nie udało się pobrać obrazu z adresu URL',
                    'file-process-failed'                 => 'Nie udało się przetworzyć niektórych plików',
                    'file-forbidden-type'                 => 'Plik ma zabroniony typ lub rozszerzenie.',
                    'file-too-large'                      => 'Plik jest za duży. Maksymalny dozwolony rozmiar to :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Zasób',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Zasób',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Zasób',
            'property'         => 'Właściwość',
            'comment'          => 'Komentarz',
            'linked_resources' => 'Powiązane zasoby',
            'directory'        => 'Katalog',
            'tag'              => 'Tag',
            'create'           => 'Utwórz',
            'edit'             => 'Edytuj',
            'update'           => 'Aktualizuj',
            'delete'           => 'Usuń',
            'list'             => 'Lista',
            'view'             => 'Podgląd',
            'upload'           => 'Prześlij',
            're_upload'        => 'Prześlij ponownie',
            'mass_update'      => 'Masowa aktualizacja',
            'mass_delete'      => 'Masowe usuwanie',
            'download'         => 'Pobierz',
            'custom_download'  => 'Niestandardowe pobieranie',
            'rename'           => 'Zmień nazwę',
            'move'             => 'Przenieś',
            'copy'             => 'Kopiuj',
            'copy-structure'   => 'Kopiuj strukturę katalogu',
            'download-zip'     => 'Pobierz Zip',
            'asset-assign'     => 'Przypisz zasób',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Pole :attribute jest wymagane.',
            ],

            'comment' => [
                'required' => 'Treść komentarza jest wymagana.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Pole tagu jest wymagane.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Pole nazwy jest wymagane.',
                    'unique'   => 'Nazwa jest już zajęta.',
                ],
                'language' => [
                    'not-found' => 'Wybrany język nie został znaleziony lub jest obecnie wyłączony.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Ta akcja jest nieautoryzowana.',
        ],
    ],
];
