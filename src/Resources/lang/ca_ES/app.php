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
                    'message' => 'Eliminar aquest directori també eliminarà tots els subdirectoris que conté. Aquesta acció és permanent i no es pot desfer.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Afegir Actiu',
                    'assign-assets' => 'Assignar Actius',
                    'assign'        => 'Assignar',
                    'preview-asset' => 'Previsualitzar Actiu',
                    'preview'       => 'Previsualitzar',
                    'remove'        => 'Eliminar',
                    'download'      => 'Descarregar',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nom del Fitxer',
                    'tags'           => 'Etiquetes',
                    'property-name'  => 'Nom de la Propietat',
                    'property-value' => 'Valor de la Propietat',
                    'created-at'     => 'Creat el',
                    'updated-at'     => 'Actualitzat el',
                    'extension'      => 'Extensió',
                    'path'           => 'Ruta',
                    'size'           => 'Mida',
                ],

                'directory' => [
                    'title'        => 'Directori',
                    'create'       => [
                        'title'    => 'Crear Directori',
                        'name'     => 'Nom',
                        'save-btn' => 'Desar Directori',
                    ],

                    'rename' => [
                        'title' => 'Canviar el nom del Directori',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Canviar el nom de l\'Actiu',
                            'save-btn' => 'Desar Actiu',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Eliminar',
                        'rename'                    => 'Canviar el nom',
                        'copy'                      => 'Copiar',
                        'download'                  => 'Descarregar',
                        'download-zip'              => 'Descarregar Zip',
                        'paste'                     => 'Enganxar',
                        'add-directory'             => 'Afegir Directori',
                        'upload-files'              => 'Pujar Fitxers',
                        'copy-directory-structured' => 'Copiar Estructura del Directori',
                        'get-by-id'                 => 'Obtenir per Id',
                        'comment'                   => 'Comentari',
                    ],

                    'linked-resources'                          => 'Recursos Vinculats',
                    'not-found'                                 => 'No s\'ha trobat cap directori',
                    'created-success'                           => 'Directori creat correctament',
                    'updated-success'                           => 'Directori actualitzat correctament',
                    'moved-success'                             => 'Directori mogut correctament',
                    'fetch-all-success'                         => 'Directoris obtinguts correctament',
                    'can-not-deleted'                           => 'No es pot eliminar el directori perquè és el Directori Arrel.',
                    'deleting-in-progress'                      => 'Eliminació del directori en curs',
                    'can-not-copy'                              => 'No es pot copiar el directori perquè és el Directori Arrel.',
                    'coping-in-progress'                        => 'Còpia de l\'estructura del directori en curs.',
                    'asset-not-found'                           => 'No s\'ha trobat cap actiu',
                    'asset-renamed-success'                     => 'Actiu reanomenat correctament',
                    'asset-moved-success'                       => 'Actiu mogut correctament',
                    'asset-name-already-exist'                  => 'El nou nom ja existeix amb un altre actiu anomenat :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'El nom de l\'actiu entra en conflicte amb un fitxer existent al mateix directori.',
                    'old-file-not-found'                        => 'El fitxer sol·licitat a la ruta :old_path no s\'ha trobat.',
                    'image-name-is-the-same'                    => 'Aquest nom ja existeix. Si us plau, introduïu-ne un altre.',
                    'not-writable'                              => 'No teniu permís per :actionType un :type en aquesta ubicació ":path".',
                    'empty-directory'                           => 'Aquest directori és buit.',
                    'failed-download-directory'                 => 'No s\'ha pogut crear el fitxer zip.',
                    'not-allowed'                               => 'No es permet la pujada de fitxers d\'script.',
                ],

                'title'       => 'DAM',
                'description' => 'L\'eina us pot ajudar a organitzar, emmagatzemar i gestionar tots els vostres actius multimèdia en un sol lloc',
                'root'        => 'Arrel',
                'upload'      => 'Pujar',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propietats de l\'Actiu',
                        'create-btn' => 'Crear Propietat',

                        'datagrid'      => [
                            'name'     => 'Nom',
                            'type'     => 'Tipus',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'edit'     => 'Editar',
                            'delete'   => 'Eliminar',
                        ],

                        'create'     => [
                            'title'    => 'Crear Propietat',
                            'name'     => 'Nom',
                            'type'     => 'Tipus',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'save-btn' => 'Desar',
                        ],
                        'edit' => [
                            'title' => 'Editar Propietat',
                        ],
                        'delete-success' => 'Propietat de l\'Actiu eliminada correctament',
                        'create-success' => 'Propietat de l\'Actiu creada correctament',
                        'update-success' => 'Propietat de l\'Actiu actualitzada correctament',
                        'not-found'      => 'Propietat no trobada',
                        'found-success'  => 'Propietat trobada correctament',
                    ],
                ],
                'comments' => [
                    'index'  => 'Afegir Comentari',
                    'create' => [
                        'create-success' => 'El comentari s\'ha afegit correctament',
                        'create-failure' => 'No s\'ha pogut crear el comentari',
                    ],
                    'post-comment'    => 'Publicar Comentari',
                    'post-reply'      => 'Publicar Resposta',
                    'reply'           => 'Respondre',
                    'add-reply'       => 'Afegir Resposta',
                    'add-comment'     => 'Afegir Comentari',
                    'no-comments'     => 'Encara no hi ha comentaris',
                    'not-found'       => 'Comentaris no trobats',
                    'updated-success' => 'Comentari actualitzat correctament',
                    'update-failed'   => 'No s\'ha pogut actualitzar el comentari',
                    'delete-success'  => 'Comentari de l\'Actiu eliminat correctament',
                    'delete-failed'   => 'No s\'ha pogut eliminar el comentari de l\'Actiu',
                ],
                'edit' => [
                    'title'              => 'Editar Actiu',
                    'name'               => 'Nom',
                    'value'              => 'Valor',
                    'back-btn'           => 'Enrere',
                    'save-btn'           => 'Desar',
                    'embedded_meta_info' => 'Informació Meta Incrustada',
                    'custom_meta_info'   => 'Informació Meta Personalitzada',
                    'tags'               => 'Etiquetes',
                    'select-tags'        => 'Escollir o Crear una Etiqueta',
                    'tag'                => 'Etiqueta',
                    'directory-path'     => 'Ruta del Directori',
                    'add_tags'           => 'Afegir Etiquetes',
                    'tab'                => [
                        'preview'          => 'Vista prèvia',
                        'properties'       => 'Propietats',
                        'comments'         => 'Comentaris',
                        'linked_resources' => 'Recursos Vinculats',
                        'history'          => 'Historial',
                    ],
                    'button' => [
                        'download'        => 'Descarregar',
                        'custom_download' => 'Descàrrega Personalitzada',
                        'rename'          => 'Canviar el nom',
                        're_upload'       => 'Tornar a Pujar',
                        'delete'          => 'Eliminar',
                    ],

                    'custom-download' => [
                        'title'              => 'Descàrrega Personalitzada',
                        'format'             => 'Format',
                        'width'              => 'Amplada (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Alçada (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Descarregar',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'L\'etiqueta ja existeix',
                    'image-source-not-readable' => 'Font de la imatge no llegible',
                    'failed-to-read'            => 'No s\'han pogut llegir les metadades de la imatge :exception',
                    'file-re-upload-success'    => 'Fitxers tornats a pujar correctament.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Producte',
                            'category'      => 'Categoria',
                            'product-sku'   => 'Sku del Producte: ',
                            'category code' => 'Codi de la Categoria: ',
                            'resource-type' => 'Tipus de Recurs',
                            'resource'      => 'Recurs',
                            'resource-view' => 'Vista del Recurs',
                        ],
                    ],
                    'found-success' => 'Recurs trobat correctament',
                    'not-found'     => 'Recurs no trobat',
                ],
                'tags' => [
                    'index'  => 'Afegir etiquetes',
                    'create' => [
                        'create-success' => 'Les etiquetes s\'han afegit correctament',
                        'create-failure' => 'No s\'han pogut crear les etiquetes',
                    ],

                    'no-comments'    => 'Encara no hi ha etiquetes',
                    'found-success'  => 'Etiqueta trobada correctament',
                    'not-found'      => 'Etiquetes no trobades',
                    'update-success' => 'Etiquetes actualitzades correctament',
                    'update-failed'  => 'No s\'han pogut actualitzar les etiquetes',
                    'delete-success' => 'Etiquetes de l\'Actiu eliminades correctament',
                    'delete-failed'  => 'No s\'han pogut eliminar les etiquetes de l\'Actiu',
                ],
                'delete-success'                          => 'Actiu eliminat correctament',
                'delete-failed-due-to-attached-resources' => 'Actiu en ús. Desvinculeu-lo abans d\'eliminar-lo',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Eliminació massiva realitzada correctament.',
                    'files-upload-success'                => 'Fitxers pujats correctament.',
                    'file-upload-success'                 => 'Fitxer pujat correctament.',
                    'not-found'                           => 'Fitxer no trobat',
                    'edit-success'                        => 'Fitxer pujat correctament',
                    'show-success'                        => 'Fitxer trobat correctament',
                    'update-success'                      => 'Fitxer actualitzat correctament',
                    'not-found-to-update'                 => 'El fitxer no existeix',
                    'not-found-to-destroy'                => 'El fitxer no existeix',
                    'files-upload-failed'                 => 'No s\'han pogut pujar els fitxers.',
                    'file-upload-failed'                  => 'No s\'ha pogut pujar el fitxer',
                    'invalid-file'                        => 'Fitxer no vàlid proporcionat',
                    'invalid-file-format'                 => 'Format no vàlid',
                    'invalid-file-format-or-not-provided' => 'No s\'han proporcionat fitxers o el format no és vàlid.',
                    'download-image-failed'               => 'No s\'ha pogut descarregar la imatge des de l\'URL',
                    'file-process-failed'                 => 'Alguns fitxers no s\'han pogut processar',
                    'file-forbidden-type'                 => 'El fitxer té un tipus o extensió prohibits.',
                    'file-too-large'                      => 'El fitxer és massa gran. La mida màxima permesa és :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Actiu',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Actiu',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Actiu',
            'property'         => 'Propietat',
            'comment'          => 'Comentari',
            'linked_resources' => 'Recursos Vinculats',
            'directory'        => 'Directori',
            'tag'              => 'Etiqueta',
            'create'           => 'Crear',
            'edit'             => 'Editar',
            'update'           => 'Actualitzar',
            'delete'           => 'Eliminar',
            'list'             => 'Llista',
            'view'             => 'Veure',
            'upload'           => 'Pujar',
            're_upload'        => 'Tornar a Pujar',
            'mass_update'      => 'Actualització Massiva',
            'mass_delete'      => 'Eliminació Massiva',
            'download'         => 'Descarregar',
            'custom_download'  => 'Descàrrega Personalitzada',
            'rename'           => 'Canviar el nom',
            'move'             => 'Moure',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar Estructura del Directori',
            'download-zip'     => 'Descarregar Zip',
            'asset-assign'     => 'Assignar Actiu',
        ],

        'validation' => [
            'asset' => [
                'required' => 'El camp :attribute és obligatori.',
            ],

            'comment' => [
                'required' => 'El missatge del comentari és obligatori.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'El camp Etiqueta és obligatori.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'El camp Nom és obligatori.',
                    'unique'   => 'El Nom ja ha estat pres.',
                ],
                'language' => [
                    'not-found' => 'L\'idioma seleccionat no s\'ha trobat o actualment està desactivat.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Aquesta acció no està autoritzada.',
        ],
    ],
];
