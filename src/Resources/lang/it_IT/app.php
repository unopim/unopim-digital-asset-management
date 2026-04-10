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
                    'message' => 'L\'eliminazione di questa directory eliminerà anche tutte le sottodirectory al suo interno. Questa azione è permanente e non può essere annullata.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Aggiungi Asset',
                    'assign-assets' => 'Assegna Asset',
                    'assign'        => 'Assegna',
                    'preview-asset' => 'Anteprima Asset',
                    'preview'       => 'Anteprima',
                    'remove'        => 'Rimuovi',
                    'download'      => 'Scarica',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nome del File',
                    'tags'           => 'Tag',
                    'property-name'  => 'Nome della Proprietà',
                    'property-value' => 'Valore della Proprietà',
                    'created-at'     => 'Creato il',
                    'updated-at'     => 'Aggiornato il',
                    'extension'      => 'Estensione',
                    'path'           => 'Percorso',
                    'size'           => 'Dimensione',
                ],

                'directory' => [
                    'title'        => 'Directory',
                    'create'       => [
                        'title'    => 'Crea Directory',
                        'name'     => 'Nome',
                        'save-btn' => 'Salva Directory',
                    ],

                    'rename' => [
                        'title' => 'Rinomina Directory',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Rinomina Asset',
                            'save-btn' => 'Salva Asset',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Elimina',
                        'rename'                    => 'Rinomina',
                        'copy'                      => 'Copia',
                        'download'                  => 'Scarica',
                        'download-zip'              => 'Scarica Zip',
                        'paste'                     => 'Incolla',
                        'add-directory'             => 'Aggiungi Directory',
                        'upload-files'              => 'Carica File',
                        'copy-directory-structured' => 'Copia Struttura Directory',
                        'get-by-id'                 => 'Ottieni per Id',
                        'comment'                   => 'Commento',
                    ],

                    'linked-resources'                          => 'Risorse Collegate',
                    'not-found'                                 => 'Nessuna directory trovata',
                    'created-success'                           => 'Directory creata con successo',
                    'updated-success'                           => 'Directory aggiornata con successo',
                    'moved-success'                             => 'Directory spostata con successo',
                    'fetch-all-success'                         => 'Directory recuperate con successo',
                    'can-not-deleted'                           => 'La directory non può essere eliminata in quanto è la Directory Radice.',
                    'deleting-in-progress'                      => 'Eliminazione della directory in corso',
                    'can-not-copy'                              => 'La directory non può essere copiata in quanto è la Directory Radice.',
                    'coping-in-progress'                        => 'Copia della struttura della directory in corso.',
                    'asset-not-found'                           => 'Nessun asset trovato',
                    'asset-renamed-success'                     => 'Asset rinominato con successo',
                    'asset-moved-success'                       => 'Asset spostato con successo',
                    'asset-name-already-exist'                  => 'Il nuovo nome esiste già con un altro asset denominato :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Il nome dell\'asset è in conflitto con un file esistente nella stessa directory.',
                    'old-file-not-found'                        => 'Il file richiesto al percorso :old_path non è stato trovato.',
                    'image-name-is-the-same'                    => 'Questo nome esiste già. Inserisci un nome diverso.',
                    'not-writable'                              => 'Non sei autorizzato a :actionType un :type in questa posizione ":path".',
                    'empty-directory'                           => 'Questa directory è vuota.',
                    'failed-download-directory'                 => 'Impossibile creare il file zip.',
                    'not-allowed'                               => 'Il caricamento di file di script non è consentito.',
                ],

                'title'       => 'DAM',
                'description' => 'Lo strumento può aiutarti a organizzare, archiviare e gestire tutti i tuoi asset multimediali in un unico luogo',
                'root'        => 'Radice',
                'upload'      => 'Carica',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Proprietà dell\'Asset',
                        'create-btn' => 'Crea Proprietà',

                        'datagrid'      => [
                            'name'     => 'Nome',
                            'type'     => 'Tipo',
                            'language' => 'Lingua',
                            'value'    => 'Valore',
                            'edit'     => 'Modifica',
                            'delete'   => 'Elimina',
                        ],

                        'create'     => [
                            'title'    => 'Crea Proprietà',
                            'name'     => 'Nome',
                            'type'     => 'Tipo',
                            'language' => 'Lingua',
                            'value'    => 'Valore',
                            'save-btn' => 'Salva',
                        ],
                        'edit' => [
                            'title' => 'Modifica Proprietà',
                        ],
                        'delete-success' => 'Proprietà dell\'Asset eliminata con successo',
                        'create-success' => 'Proprietà dell\'Asset creata con successo',
                        'update-success' => 'Proprietà dell\'Asset aggiornata con successo',
                        'not-found'      => 'Proprietà non trovata',
                        'found-success'  => 'Proprietà trovata con successo',
                    ],
                ],
                'comments' => [
                    'index'  => 'Aggiungi Commento',
                    'create' => [
                        'create-success' => 'Il commento è stato aggiunto con successo',
                        'create-failure' => 'Creazione del commento non riuscita',
                    ],
                    'post-comment'    => 'Pubblica Commento',
                    'post-reply'      => 'Pubblica Risposta',
                    'reply'           => 'Rispondi',
                    'add-reply'       => 'Aggiungi Risposta',
                    'add-comment'     => 'Aggiungi Commento',
                    'no-comments'     => 'Ancora nessun commento',
                    'not-found'       => 'Commenti non trovati',
                    'updated-success' => 'Commento aggiornato con successo',
                    'update-failed'   => 'Aggiornamento del commento non riuscito',
                    'delete-success'  => 'Commento dell\'Asset eliminato con successo',
                    'delete-failed'   => 'Eliminazione del commento dell\'Asset non riuscita',
                ],
                'edit' => [
                    'title'              => 'Modifica Asset',
                    'name'               => 'Nome',
                    'value'              => 'Valore',
                    'back-btn'           => 'Indietro',
                    'save-btn'           => 'Salva',
                    'embedded_meta_info' => 'Informazioni Meta Incorporate',
                    'custom_meta_info'   => 'Informazioni Meta Personalizzate',
                    'tags'               => 'Tag',
                    'select-tags'        => 'Scegli o Crea un Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Percorso della Directory',
                    'add_tags'           => 'Aggiungi Tag',
                    'tab'                => [
                        'preview'          => 'Anteprima',
                        'properties'       => 'Proprietà',
                        'comments'         => 'Commenti',
                        'linked_resources' => 'Risorse Collegate',
                        'history'          => 'Cronologia',
                    ],
                    'button' => [
                        'download'        => 'Scarica',
                        'custom_download' => 'Download Personalizzato',
                        'rename'          => 'Rinomina',
                        're_upload'       => 'Ricarica',
                        'delete'          => 'Elimina',
                    ],

                    'custom-download' => [
                        'title'              => 'Download Personalizzato',
                        'format'             => 'Formato',
                        'width'              => 'Larghezza (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Altezza (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Scarica',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Originale',
                        ],
                    ],

                    'tag-already-exists'        => 'Il tag esiste già',
                    'image-source-not-readable' => 'Origine dell\'immagine non leggibile',
                    'failed-to-read'            => 'Impossibile leggere i metadati dell\'immagine :exception',
                    'file-re-upload-success'    => 'File ricaricati con successo.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Prodotto',
                            'category'      => 'Categoria',
                            'product-sku'   => 'Sku Prodotto: ',
                            'category code' => 'Codice Categoria: ',
                            'resource-type' => 'Tipo di Risorsa',
                            'resource'      => 'Risorsa',
                            'resource-view' => 'Vista Risorsa',
                        ],
                    ],
                    'found-success' => 'Risorsa trovata con successo',
                    'not-found'     => 'Risorsa non trovata',
                ],
                'tags' => [
                    'index'  => 'Aggiungi tag',
                    'create' => [
                        'create-success' => 'I tag sono stati aggiunti con successo',
                        'create-failure' => 'Creazione dei tag non riuscita',
                    ],

                    'no-comments'    => 'Ancora nessun tag',
                    'found-success'  => 'Tag trovato con successo',
                    'not-found'      => 'Tag non trovati',
                    'update-success' => 'Tag aggiornati con successo',
                    'update-failed'  => 'Aggiornamento dei tag non riuscito',
                    'delete-success' => 'Tag dell\'Asset rimossi con successo',
                    'delete-failed'  => 'Eliminazione dei tag dell\'Asset non riuscita',
                ],
                'delete-success'                          => 'Asset eliminato con successo',
                'delete-failed-due-to-attached-resources' => 'Asset in uso. Scollegalo prima di eliminarlo',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Eliminazione di massa completata con successo.',
                    'files-upload-success'                => 'File caricati con successo.',
                    'file-upload-success'                 => 'File caricato con successo.',
                    'not-found'                           => 'File non trovato',
                    'edit-success'                        => 'File caricato con successo',
                    'show-success'                        => 'File trovato con successo',
                    'update-success'                      => 'File aggiornato con successo',
                    'not-found-to-update'                 => 'Il file non esiste',
                    'not-found-to-destroy'                => 'Il file non esiste',
                    'files-upload-failed'                 => 'Caricamento dei file non riuscito.',
                    'file-upload-failed'                  => 'Caricamento del file non riuscito',
                    'invalid-file'                        => 'File non valido fornito',
                    'invalid-file-format'                 => 'Formato non valido',
                    'invalid-file-format-or-not-provided' => 'Nessun file fornito o formato non valido.',
                    'download-image-failed'               => 'Impossibile scaricare l\'immagine dall\'URL',
                    'file-process-failed'                 => 'Elaborazione di alcuni file non riuscita',
                    'file-forbidden-type'                 => 'Il file ha un tipo o un\'estensione non consentita.',
                    'file-too-large'                      => 'Il file è troppo grande. La dimensione massima consentita è :size.',
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
            'property'         => 'Proprietà',
            'comment'          => 'Commento',
            'linked_resources' => 'Risorse Collegate',
            'directory'        => 'Directory',
            'tag'              => 'Tag',
            'create'           => 'Crea',
            'edit'             => 'Modifica',
            'update'           => 'Aggiorna',
            'delete'           => 'Elimina',
            'list'             => 'Elenco',
            'view'             => 'Visualizza',
            'upload'           => 'Carica',
            're_upload'        => 'Ricarica',
            'mass_update'      => 'Aggiornamento di massa',
            'mass_delete'      => 'Eliminazione di massa',
            'download'         => 'Scarica',
            'custom_download'  => 'Download personalizzato',
            'rename'           => 'Rinomina',
            'move'             => 'Sposta',
            'copy'             => 'Copia',
            'copy-structure'   => 'Copia struttura directory',
            'download-zip'     => 'Scarica Zip',
            'asset-assign'     => 'Assegna Asset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Il campo :attribute è obbligatorio.',
            ],

            'comment' => [
                'required' => 'Il messaggio del commento è obbligatorio.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Il campo Tag è obbligatorio.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Il campo Nome è obbligatorio.',
                    'unique'   => 'Il Nome è già stato utilizzato.',
                ],
                'language' => [
                    'not-found' => 'La lingua selezionata non è stata trovata o è attualmente disabilitata.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Questa azione non è autorizzata.',
        ],
    ],
];
