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
                    'message' => 'Ștergerea acestui director va șterge, de asemenea, toate subdirectoarele din interior. Această acțiune este permanentă și nu poate fi anulată.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Adaugă Activ',
                    'assign-assets' => 'Atribuie Active',
                    'assign'        => 'Atribuie',
                    'preview-asset' => 'Previzualizare Activ',
                    'preview'       => 'Previzualizare',
                    'remove'        => 'Elimină',
                    'download'      => 'Descarcă',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nume Fișier',
                    'tags'           => 'Etichete',
                    'property-name'  => 'Nume Proprietate',
                    'property-value' => 'Valoare Proprietate',
                    'created-at'     => 'Creat la',
                    'updated-at'     => 'Actualizat la',
                    'extension'      => 'Extensie',
                    'path'           => 'Cale',
                    'size'           => 'Dimensiune',
                ],

                'directory' => [
                    'title'        => 'Director',
                    'create'       => [
                        'title'    => 'Creează Director',
                        'name'     => 'Nume',
                        'save-btn' => 'Salvează Directorul',
                    ],

                    'rename' => [
                        'title' => 'Redenumește Directorul',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Redenumește Activul',
                            'save-btn' => 'Salvează Activul',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Șterge',
                        'rename'                    => 'Redenumește',
                        'copy'                      => 'Copiază',
                        'download'                  => 'Descarcă',
                        'download-zip'              => 'Descarcă Zip',
                        'paste'                     => 'Lipește',
                        'add-directory'             => 'Adaugă Director',
                        'upload-files'              => 'Încarcă Fișiere',
                        'copy-directory-structured' => 'Copiază Structura Directorului',
                        'get-by-id'                 => 'Obține după Id',
                        'comment'                   => 'Comentariu',
                    ],

                    'linked-resources'                          => 'Resurse Asociate',
                    'not-found'                                 => 'Niciun director găsit',
                    'created-success'                           => 'Director creat cu succes',
                    'updated-success'                           => 'Director actualizat cu succes',
                    'moved-success'                             => 'Director mutat cu succes',
                    'fetch-all-success'                         => 'Directoare preluate cu succes',
                    'can-not-deleted'                           => 'Directorul nu poate fi șters deoarece este Directorul Rădăcină.',
                    'deleting-in-progress'                      => 'Ștergerea directorului este în curs',
                    'can-not-copy'                              => 'Directorul nu poate fi copiat deoarece este Directorul Rădăcină.',
                    'coping-in-progress'                        => 'Copierea structurii directorului este în curs.',
                    'asset-not-found'                           => 'Niciun activ găsit',
                    'asset-renamed-success'                     => 'Activ redenumit cu succes',
                    'asset-moved-success'                       => 'Activ mutat cu succes',
                    'asset-name-already-exist'                  => 'Noul nume există deja cu un alt activ numit :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Numele activului intră în conflict cu un fișier existent din același director.',
                    'old-file-not-found'                        => 'Fișierul solicitat la calea :old_path nu a fost găsit.',
                    'image-name-is-the-same'                    => 'Acest nume există deja. Vă rugăm să introduceți un alt nume.',
                    'not-writable'                              => 'Nu aveți permisiunea să :actionType un :type în această locație ":path".',
                    'empty-directory'                           => 'Acest director este gol.',
                    'failed-download-directory'                 => 'Crearea fișierului zip a eșuat.',
                    'not-allowed'                               => 'Încărcarea fișierelor de tip script nu este permisă.',
                ],

                'title'       => 'DAM',
                'description' => 'Instrumentul vă poate ajuta să organizați, să stocați și să gestionați toate activele media într-un singur loc',
                'root'        => 'Rădăcină',
                'upload'      => 'Încarcă',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Proprietățile Activului',
                        'create-btn' => 'Creează Proprietate',

                        'datagrid'      => [
                            'name'     => 'Nume',
                            'type'     => 'Tip',
                            'language' => 'Limbă',
                            'value'    => 'Valoare',
                            'edit'     => 'Editează',
                            'delete'   => 'Șterge',
                        ],

                        'create'     => [
                            'title'    => 'Creează Proprietate',
                            'name'     => 'Nume',
                            'type'     => 'Tip',
                            'language' => 'Limbă',
                            'value'    => 'Valoare',
                            'save-btn' => 'Salvează',
                        ],
                        'edit' => [
                            'title' => 'Editează Proprietatea',
                        ],
                        'delete-success' => 'Proprietatea Activului a fost ștearsă cu succes',
                        'create-success' => 'Proprietatea Activului a fost creată cu succes',
                        'update-success' => 'Proprietatea Activului a fost actualizată cu succes',
                        'not-found'      => 'Proprietatea nu a fost găsită',
                        'found-success'  => 'Proprietatea a fost găsită cu succes',
                    ],
                ],
                'comments' => [
                    'index'  => 'Adaugă Comentariu',
                    'create' => [
                        'create-success' => 'Comentariul a fost adăugat cu succes',
                        'create-failure' => 'Crearea comentariului a eșuat',
                    ],
                    'post-comment'    => 'Publică Comentariu',
                    'post-reply'      => 'Publică Răspuns',
                    'reply'           => 'Răspunde',
                    'add-reply'       => 'Adaugă Răspuns',
                    'add-comment'     => 'Adaugă Comentariu',
                    'no-comments'     => 'Încă nu există comentarii',
                    'not-found'       => 'Comentariile nu au fost găsite',
                    'updated-success' => 'Comentariu actualizat cu succes',
                    'update-failed'   => 'Actualizarea comentariului a eșuat',
                    'delete-success'  => 'Comentariul Activului a fost șters cu succes',
                    'delete-failed'   => 'Ștergerea comentariului Activului a eșuat',
                ],
                'edit' => [
                    'title'              => 'Editează Activul',
                    'name'               => 'Nume',
                    'value'              => 'Valoare',
                    'back-btn'           => 'Înapoi',
                    'save-btn'           => 'Salvează',
                    'embedded_meta_info' => 'Informații Meta Încorporate',
                    'custom_meta_info'   => 'Informații Meta Personalizate',
                    'tags'               => 'Etichete',
                    'select-tags'        => 'Alege sau Creează o Etichetă',
                    'tag'                => 'Etichetă',
                    'directory-path'     => 'Calea Directorului',
                    'add_tags'           => 'Adaugă Etichete',
                    'tab'                => [
                        'preview'          => 'Previzualizare',
                        'properties'       => 'Proprietăți',
                        'comments'         => 'Comentarii',
                        'linked_resources' => 'Resurse Asociate',
                        'history'          => 'Istoric',
                    ],
                    'button' => [
                        'download'        => 'Descarcă',
                        'custom_download' => 'Descărcare Personalizată',
                        'rename'          => 'Redenumește',
                        're_upload'       => 'Reîncarcă',
                        'delete'          => 'Șterge',
                    ],

                    'custom-download' => [
                        'title'              => 'Descărcare Personalizată',
                        'format'             => 'Format',
                        'width'              => 'Lățime (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Înălțime (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Descarcă',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Eticheta există deja',
                    'image-source-not-readable' => 'Sursa imaginii nu poate fi citită',
                    'failed-to-read'            => 'Citirea metadatelor imaginii a eșuat :exception',
                    'file-re-upload-success'    => 'Fișierele au fost reîncărcate cu succes.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produs',
                            'category'      => 'Categorie',
                            'product-sku'   => 'Sku Produs: ',
                            'category code' => 'Cod Categorie: ',
                            'resource-type' => 'Tip de Resursă',
                            'resource'      => 'Resursă',
                            'resource-view' => 'Vizualizare Resursă',
                        ],
                    ],
                    'found-success' => 'Resursa a fost găsită cu succes',
                    'not-found'     => 'Resursa nu a fost găsită',
                ],
                'tags' => [
                    'index'  => 'Adaugă etichete',
                    'create' => [
                        'create-success' => 'Etichetele au fost adăugate cu succes',
                        'create-failure' => 'Crearea etichetelor a eșuat',
                    ],

                    'no-comments'    => 'Încă nu există etichete',
                    'found-success'  => 'Eticheta a fost găsită cu succes',
                    'not-found'      => 'Etichetele nu au fost găsite',
                    'update-success' => 'Etichete actualizate cu succes',
                    'update-failed'  => 'Actualizarea etichetelor a eșuat',
                    'delete-success' => 'Etichetele Activului au fost eliminate cu succes',
                    'delete-failed'  => 'Ștergerea etichetelor Activului a eșuat',
                ],
                'delete-success'                          => 'Activ șters cu succes',
                'delete-failed-due-to-attached-resources' => 'Activul este utilizat. Dezasociați-l înainte de ștergere',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Ștergere în masă efectuată cu succes.',
                    'files-upload-success'                => 'Fișiere încărcate cu succes.',
                    'file-upload-success'                 => 'Fișier încărcat cu succes.',
                    'not-found'                           => 'Fișierul nu a fost găsit',
                    'edit-success'                        => 'Fișier încărcat cu succes',
                    'show-success'                        => 'Fișier găsit cu succes',
                    'update-success'                      => 'Fișier actualizat cu succes',
                    'not-found-to-update'                 => 'Fișierul nu există',
                    'not-found-to-destroy'                => 'Fișierul nu există',
                    'files-upload-failed'                 => 'Încărcarea fișierelor a eșuat.',
                    'file-upload-failed'                  => 'Încărcarea fișierului a eșuat',
                    'invalid-file'                        => 'Fișier invalid furnizat',
                    'invalid-file-format'                 => 'Format invalid',
                    'invalid-file-format-or-not-provided' => 'Niciun fișier furnizat sau format invalid.',
                    'download-image-failed'               => 'Descărcarea imaginii de la URL a eșuat',
                    'file-process-failed'                 => 'Unele fișiere nu au putut fi procesate',
                    'file-forbidden-type'                 => 'Fișierul are un tip sau o extensie interzisă.',
                    'file-too-large'                      => 'Fișierul este prea mare. Dimensiunea maximă permisă este :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Activ',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Activ',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Activ',
            'property'         => 'Proprietate',
            'comment'          => 'Comentariu',
            'linked_resources' => 'Resurse Asociate',
            'directory'        => 'Director',
            'tag'              => 'Etichetă',
            'create'           => 'Creează',
            'edit'             => 'Editează',
            'update'           => 'Actualizează',
            'delete'           => 'Șterge',
            'list'             => 'Listă',
            'view'             => 'Vizualizează',
            'upload'           => 'Încarcă',
            're_upload'        => 'Reîncarcă',
            'mass_update'      => 'Actualizare în Masă',
            'mass_delete'      => 'Ștergere în Masă',
            'download'         => 'Descarcă',
            'custom_download'  => 'Descărcare Personalizată',
            'rename'           => 'Redenumește',
            'move'             => 'Mută',
            'copy'             => 'Copiază',
            'copy-structure'   => 'Copiază Structura Directorului',
            'download-zip'     => 'Descarcă Zip',
            'asset-assign'     => 'Atribuie Activ',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Câmpul :attribute este obligatoriu.',
            ],

            'comment' => [
                'required' => 'Mesajul comentariului este obligatoriu.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Câmpul Etichetă este obligatoriu.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Câmpul Nume este obligatoriu.',
                    'unique'   => 'Numele este deja utilizat.',
                ],
                'language' => [
                    'not-found' => 'Limba selectată nu a putut fi găsită sau este momentan dezactivată.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Această acțiune nu este autorizată.',
        ],
    ],
];
