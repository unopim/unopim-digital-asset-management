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
                    'message' => 'Brisanjem ovog direktorija obrisat će se i svi poddirektoriji unutar njega. Ova je radnja trajna i ne može se poništiti.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Dodaj resurs',
                    'assign-assets' => 'Dodijeli resurse',
                    'assign'        => 'Dodijeli',
                    'preview-asset' => 'Pregled resursa',
                    'preview'       => 'Pregled',
                    'remove'        => 'Ukloni',
                    'download'      => 'Preuzmi',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Naziv datoteke',
                    'tags'           => 'Oznake',
                    'property-name'  => 'Naziv svojstva',
                    'property-value' => 'Vrijednost svojstva',
                    'created-at'     => 'Datum stvaranja',
                    'updated-at'     => 'Datum ažuriranja',
                    'extension'      => 'Ekstenzija',
                    'path'           => 'Putanja',
                    'size'           => 'Veličina',
                ],

                'directory' => [
                    'title'  => 'Direktorij',
                    'create' => [
                        'title'    => 'Stvori direktorij',
                        'name'     => 'Naziv',
                        'save-btn' => 'Spremi direktorij',
                    ],

                    'rename' => [
                        'title' => 'Preimenuj direktorij',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Preimenuj resurs',
                            'save-btn' => 'Spremi resurs',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Obriši',
                        'rename'                    => 'Preimenuj',
                        'copy'                      => 'Kopiraj',
                        'download'                  => 'Preuzmi',
                        'download-zip'              => 'Preuzmi Zip',
                        'paste'                     => 'Zalijepi',
                        'add-directory'             => 'Dodaj direktorij',
                        'upload-files'              => 'Prenesi datoteke',
                        'copy-directory-structured' => 'Kopiraj strukturu direktorija',
                        'get-by-id'                 => 'Dohvati po ID-u',
                        'comment'                   => 'Komentar',
                    ],

                    'linked-resources'                          => 'Povezani resursi',
                    'not-found'                                 => 'Direktorij nije pronađen',
                    'created-success'                           => 'Direktorij je uspješno stvoren',
                    'updated-success'                           => 'Direktorij je uspješno ažuriran',
                    'moved-success'                             => 'Direktorij je uspješno premješten',
                    'fetch-all-success'                         => 'Direktoriji su uspješno dohvaćeni',
                    'can-not-deleted'                           => 'Direktorij se ne može obrisati jer je korijenski direktorij.',
                    'deleting-in-progress'                      => 'Brisanje direktorija je u tijeku',
                    'can-not-copy'                              => 'Direktorij se ne može kopirati jer je korijenski direktorij.',
                    'coping-in-progress'                        => 'Kopiranje strukture direktorija je u tijeku.',
                    'asset-not-found'                           => 'Resurs nije pronađen',
                    'asset-renamed-success'                     => 'Resurs je uspješno preimenovan',
                    'asset-moved-success'                       => 'Resurs je uspješno premješten',
                    'asset-name-already-exist'                  => 'Novi naziv već postoji kod drugog resursa pod nazivom :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Naziv resursa je u sukobu s postojećom datotekom u istom direktoriju.',
                    'old-file-not-found'                        => 'Datoteka zatražena na putanji :old_path nije pronađena.',
                    'image-name-is-the-same'                    => 'Ovaj naziv već postoji. Molimo unesite drugi.',
                    'not-writable'                              => 'Nije vam dopušteno :actionType :type na ovoj lokaciji ":path".',
                    'empty-directory'                           => 'Ovaj direktorij je prazan.',
                    'failed-download-directory'                 => 'Neuspješno stvaranje zip datoteke.',
                    'not-allowed'                               => 'Prijenos skriptnih datoteka nije dopušten.',
                ],

                'title'       => 'DAM',
                'description' => 'Alat koji vam pomaže organizirati, pohraniti i upravljati svim vašim medijskim resursima na jednom mjestu',
                'root'        => 'Korijen',
                'upload'      => 'Prenesi',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Svojstva resursa',
                        'create-btn' => 'Stvori svojstvo',

                        'datagrid' => [
                            'name'     => 'Naziv',
                            'type'     => 'Vrsta',
                            'language' => 'Jezik',
                            'value'    => 'Vrijednost',
                            'edit'     => 'Uredi',
                            'delete'   => 'Obriši',
                        ],

                        'create' => [
                            'title'    => 'Stvori svojstvo',
                            'name'     => 'Naziv',
                            'type'     => 'Vrsta',
                            'language' => 'Jezik',
                            'value'    => 'Vrijednost',
                            'save-btn' => 'Spremi',
                        ],
                        'edit' => [
                            'title' => 'Uredi svojstvo',
                        ],
                        'delete-success' => 'Svojstvo resursa uspješno obrisano',
                        'create-success' => 'Svojstvo resursa uspješno stvoreno',
                        'update-success' => 'Svojstvo resursa uspješno ažurirano',
                        'not-found'      => 'Svojstvo nije pronađeno',
                        'found-success'  => 'Svojstvo uspješno pronađeno',
                    ],
                ],
                'comments' => [
                    'index'  => 'Dodaj komentar',
                    'create' => [
                        'create-success' => 'Komentar je uspješno dodan',
                        'create-failure' => 'Stvaranje komentara nije uspjelo',
                    ],
                    'post-comment'    => 'Objavi komentar',
                    'post-reply'      => 'Objavi odgovor',
                    'reply'           => 'Odgovori',
                    'add-reply'       => 'Dodaj odgovor',
                    'add-comment'     => 'Dodaj komentar',
                    'no-comments'     => 'Još nema komentara',
                    'not-found'       => 'Komentari nisu pronađeni',
                    'updated-success' => 'Komentar je uspješno ažuriran',
                    'update-failed'   => 'Ažuriranje komentara nije uspjelo',
                    'delete-success'  => 'Komentar resursa uspješno obrisan',
                    'delete-failed'   => 'Brisanje komentara resursa nije uspjelo',
                ],
                'edit' => [
                    'title'              => 'Uredi resurs',
                    'name'               => 'Naziv',
                    'value'              => 'Vrijednost',
                    'back-btn'           => 'Natrag',
                    'save-btn'           => 'Spremi',
                    'embedded_meta_info' => 'Ugrađeni meta podaci',
                    'custom_meta_info'   => 'Prilagođeni meta podaci',
                    'tags'               => 'Oznake',
                    'select-tags'        => 'Odaberite ili stvorite oznaku',
                    'tag'                => 'Oznaka',
                    'directory-path'     => 'Putanja direktorija',
                    'add_tags'           => 'Dodaj oznake',
                    'tab'                => [
                        'preview'          => 'Pregled',
                        'properties'       => 'Svojstva',
                        'comments'         => 'Komentari',
                        'linked_resources' => 'Povezani resursi',
                        'history'          => 'Povijest',
                    ],
                    'button' => [
                        'download'        => 'Preuzmi',
                        'custom_download' => 'Prilagođeno preuzimanje',
                        'rename'          => 'Preimenuj',
                        're_upload'       => 'Ponovno prenesi',
                        'delete'          => 'Obriši',
                    ],

                    'custom-download' => [
                        'title'              => 'Prilagođeno preuzimanje',
                        'format'             => 'Format',
                        'width'              => 'Širina (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Visina (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Preuzmi',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Oznaka već postoji',
                    'image-source-not-readable' => 'Izvor slike nije čitljiv',
                    'failed-to-read'            => 'Nije uspjelo čitanje meta podataka slike :exception',
                    'file-re-upload-success'    => 'Datoteke su uspješno ponovno prenesene.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Proizvod',
                            'category'      => 'Kategorija',
                            'product-sku'   => 'Sku proizvoda: ',
                            'category code' => 'Kod kategorije: ',
                            'resource-type' => 'Vrsta resursa',
                            'resource'      => 'Resurs',
                            'resource-view' => 'Prikaz resursa',
                        ],
                    ],
                    'found-success' => 'Resurs uspješno pronađen',
                    'not-found'     => 'Resurs nije pronađen',
                ],
                'tags' => [
                    'index'  => 'Dodaj oznake',
                    'create' => [
                        'create-success' => 'Oznake su uspješno dodane',
                        'create-failure' => 'Stvaranje oznaka nije uspjelo',
                    ],

                    'no-comments'    => 'Još nema oznaka',
                    'found-success'  => 'Oznaka uspješno pronađena',
                    'not-found'      => 'Oznake nisu pronađene',
                    'update-success' => 'Oznake su uspješno ažurirane',
                    'update-failed'  => 'Ažuriranje oznaka nije uspjelo',
                    'delete-success' => 'Oznake resursa uspješno uklonjene',
                    'delete-failed'  => 'Brisanje oznaka resursa nije uspjelo',
                ],
                'delete-success'                          => 'Resurs je uspješno obrisan',
                'delete-failed-due-to-attached-resources' => 'Resurs je u upotrebi. Odvojite ga prije brisanja',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Masovno brisanje uspješno.',
                    'files-upload-success'                => 'Datoteke su uspješno prenesene.',
                    'file-upload-success'                 => 'Datoteka je uspješno prenesena.',
                    'not-found'                           => 'Datoteka nije pronađena',
                    'edit-success'                        => 'Datoteka je uspješno prenesena',
                    'show-success'                        => 'Datoteka je uspješno pronađena',
                    'update-success'                      => 'Datoteka je uspješno ažurirana',
                    'not-found-to-update'                 => 'Datoteka ne postoji',
                    'not-found-to-destroy'                => 'Datoteka ne postoji',
                    'files-upload-failed'                 => 'Prijenos datoteka nije uspio.',
                    'file-upload-failed'                  => 'Prijenos datoteke nije uspio',
                    'invalid-file'                        => 'Pružena je nevažeća datoteka',
                    'invalid-file-format'                 => 'Nevažeći format',
                    'invalid-file-format-or-not-provided' => 'Datoteke nisu pružene ili je format nevažeći.',
                    'download-image-failed'               => 'Nije uspjelo preuzimanje slike s URL-a',
                    'file-process-failed'                 => 'Neke datoteke nisu obrađene',
                    'file-forbidden-type'                 => 'Datoteka ima zabranjenu vrstu ili ekstenziju.',
                    'file-too-large'                      => 'Datoteka je prevelika. Maksimalno dopuštena veličina je :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Resurs',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Resurs',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Resurs',
            'property'         => 'Svojstvo',
            'comment'          => 'Komentar',
            'linked_resources' => 'Povezani resursi',
            'directory'        => 'Direktorij',
            'tag'              => 'Oznaka',
            'create'           => 'Stvori',
            'edit'             => 'Uredi',
            'update'           => 'Ažuriraj',
            'delete'           => 'Obriši',
            'list'             => 'Popis',
            'view'             => 'Pregled',
            'upload'           => 'Prenesi',
            're_upload'        => 'Ponovno prenesi',
            'mass_update'      => 'Masovno ažuriranje',
            'mass_delete'      => 'Masovno brisanje',
            'download'         => 'Preuzmi',
            'custom_download'  => 'Prilagođeno preuzimanje',
            'rename'           => 'Preimenuj',
            'move'             => 'Premjesti',
            'copy'             => 'Kopiraj',
            'copy-structure'   => 'Kopiraj strukturu direktorija',
            'download-zip'     => 'Preuzmi Zip',
            'asset-assign'     => 'Dodijeli resurs',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Polje :attribute je obavezno.',
            ],

            'comment' => [
                'required' => 'Poruka komentara je obavezna.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Polje oznake je obavezno.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Polje naziva je obavezno.',
                    'unique'   => 'Naziv je već zauzet.',
                ],
                'language' => [
                    'not-found' => 'Odabrani jezik nije pronađen ili je trenutno onemogućen.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Ova radnja nije ovlaštena.',
        ],
    ],
];
