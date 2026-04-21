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
                    'message' => 'Eliminar este directorio también eliminará todos los subdirectorios que contiene. Esta acción es permanente y no se puede deshacer.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Agregar activo',
                    'assign-assets' => 'Asignar activos',
                    'assign'        => 'Asignar',
                    'preview-asset' => 'Vista previa del activo',
                    'preview'       => 'Vista previa',
                    'remove'        => 'Eliminar',
                    'download'      => 'Descargar',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nombre del archivo',
                    'tags'           => 'Etiquetas',
                    'property-name'  => 'Nombre de la propiedad',
                    'property-value' => 'Valor de la propiedad',
                    'created-at'     => 'Creado el',
                    'updated-at'     => 'Actualizado el',
                    'extension'      => 'Extensión',
                    'path'           => 'Ruta',
                    'size'           => 'Tamaño',
                ],

                'directory' => [
                    'title'        => 'Directorio',
                    'create'       => [
                        'title'    => 'Crear directorio',
                        'name'     => 'Nombre',
                        'save-btn' => 'Guardar directorio',
                    ],

                    'rename' => [
                        'title' => 'Cambiar nombre del directorio',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Cambiar nombre del activo',
                            'save-btn' => 'Guardar activo',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Eliminar',
                        'rename'                    => 'Cambiar nombre',
                        'copy'                      => 'Copiar',
                        'download'                  => 'Descargar',
                        'download-zip'              => 'Descargar Zip',
                        'paste'                     => 'Pegar',
                        'add-directory'             => 'Agregar directorio',
                        'upload-files'              => 'Subir archivos',
                        'copy-directory-structured' => 'Copiar estructura del directorio',
                        'get-by-id'                 => 'Obtener por Id',
                        'comment'                   => 'Comentario',
                    ],

                    'linked-resources'                          => 'Recursos vinculados',
                    'not-found'                                 => 'No se encontró ningún directorio',
                    'created-success'                           => 'Directorio creado exitosamente',
                    'updated-success'                           => 'Directorio actualizado exitosamente',
                    'moved-success'                             => 'Directorio movido exitosamente',
                    'fetch-all-success'                         => 'Directorios obtenidos exitosamente',
                    'can-not-deleted'                           => 'No se puede eliminar el directorio porque es el Directorio Raíz.',
                    'deleting-in-progress'                      => 'Eliminación del directorio en progreso',
                    'can-not-copy'                              => 'No se puede copiar el directorio porque es el Directorio Raíz.',
                    'coping-in-progress'                        => 'Copia de la estructura del directorio en progreso.',
                    'asset-not-found'                           => 'No se encontró ningún activo',
                    'asset-renamed-success'                     => 'Nombre del activo cambiado exitosamente',
                    'asset-moved-success'                       => 'Activo movido exitosamente',
                    'asset-name-already-exist'                  => 'El nuevo nombre ya existe con otro activo llamado :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'El nombre del activo entra en conflicto con un archivo existente en el mismo directorio.',
                    'old-file-not-found'                        => 'El archivo solicitado en la ruta :old_path no fue encontrado.',
                    'image-name-is-the-same'                    => 'Este nombre ya existe. Por favor, ingrese uno diferente.',
                    'not-writable'                              => 'No tiene permiso para :actionType un :type en esta ubicación ":path".',
                    'empty-directory'                           => 'Este directorio está vacío.',
                    'failed-download-directory'                 => 'No se pudo crear el archivo zip.',
                    'not-allowed'                               => 'No se permite la carga de archivos de script.',
                ],

                'title'       => 'DAM',
                'description' => 'La herramienta te ayuda a organizar, almacenar y gestionar todos tus activos multimedia en un solo lugar',
                'root'        => 'Raíz',
                'upload'      => 'Subir',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propiedades del activo',
                        'create-btn' => 'Crear propiedad',

                        'datagrid'      => [
                            'name'     => 'Nombre',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'edit'     => 'Editar',
                            'delete'   => 'Eliminar',
                        ],

                        'create'     => [
                            'title'    => 'Crear propiedad',
                            'name'     => 'Nombre',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'save-btn' => 'Guardar',
                        ],
                        'edit' => [
                            'title' => 'Editar propiedad',
                        ],
                        'delete-success' => 'Propiedad del activo eliminada exitosamente',
                        'create-success' => 'Propiedad del activo creada exitosamente',
                        'update-success' => 'Propiedad del activo actualizada exitosamente',
                        'not-found'      => 'Propiedad no encontrada',
                        'found-success'  => 'Propiedad encontrada exitosamente',
                    ],
                ],
                'comments' => [
                    'index'  => 'Agregar comentario',
                    'create' => [
                        'create-success' => 'El comentario fue agregado exitosamente',
                        'create-failure' => 'Falló la creación del comentario',
                    ],
                    'post-comment'    => 'Publicar comentario',
                    'post-reply'      => 'Publicar respuesta',
                    'reply'           => 'Responder',
                    'add-reply'       => 'Agregar respuesta',
                    'add-comment'     => 'Agregar comentario',
                    'no-comments'     => 'Aún no hay comentarios',
                    'not-found'       => 'Comentarios no encontrados',
                    'updated-success' => 'Comentario actualizado exitosamente',
                    'update-failed'   => 'Falló la actualización del comentario',
                    'delete-success'  => 'Comentario del activo eliminado exitosamente',
                    'delete-failed'   => 'Falló la eliminación del comentario del activo',
                ],
                'edit' => [
                    'title'              => 'Editar activo',
                    'name'               => 'Nombre',
                    'value'              => 'Valor',
                    'back-btn'           => 'Volver',
                    'save-btn'           => 'Guardar',
                    'embedded_meta_info' => 'Información meta incrustada',
                    'custom_meta_info'   => 'Información meta personalizada',
                    'tags'               => 'Etiquetas',
                    'select-tags'        => 'Elegir o crear una etiqueta',
                    'tag'                => 'Etiqueta',
                    'directory-path'     => 'Ruta del directorio',
                    'add_tags'           => 'Agregar etiquetas',
                    'tab'                => [
                        'preview'          => 'Vista previa',
                        'properties'       => 'Propiedades',
                        'comments'         => 'Comentarios',
                        'linked_resources' => 'Recursos vinculados',
                        'history'          => 'Historial',
                    ],
                    'button' => [
                        'download'        => 'Descargar',
                        'custom_download' => 'Descarga personalizada',
                        'rename'          => 'Cambiar nombre',
                        're_upload'       => 'Volver a subir',
                        'delete'          => 'Eliminar',
                    ],

                    'custom-download' => [
                        'title'              => 'Descarga personalizada',
                        'format'             => 'Formato',
                        'width'              => 'Ancho (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Alto (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Descargar',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'La etiqueta ya existe',
                    'image-source-not-readable' => 'Fuente de la imagen no legible',
                    'failed-to-read'            => 'No se pudieron leer los metadatos de la imagen :exception',
                    'file-re-upload-success'    => 'Archivos vueltos a subir exitosamente.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Producto',
                            'category'      => 'Categoría',
                            'product-sku'   => 'Sku del producto: ',
                            'category code' => 'Código de la categoría: ',
                            'resource-type' => 'Tipo de recurso',
                            'resource'      => 'Recurso',
                            'resource-view' => 'Vista del recurso',
                        ],
                    ],
                    'found-success' => 'Recurso encontrado exitosamente',
                    'not-found'     => 'Recurso no encontrado',
                ],
                'tags' => [
                    'index'  => 'Agregar etiquetas',
                    'create' => [
                        'create-success' => 'Las etiquetas fueron agregadas exitosamente',
                        'create-failure' => 'Falló la creación de las etiquetas',
                    ],

                    'no-comments'    => 'Aún no hay etiquetas',
                    'found-success'  => 'Etiqueta encontrada exitosamente',
                    'not-found'      => 'Etiquetas no encontradas',
                    'update-success' => 'Etiquetas actualizadas exitosamente',
                    'update-failed'  => 'Falló la actualización de las etiquetas',
                    'delete-success' => 'Etiquetas del activo eliminadas exitosamente',
                    'delete-failed'  => 'Falló la eliminación de las etiquetas del activo',
                ],
                'delete-success'                          => 'Activo eliminado exitosamente',
                'delete-failed-due-to-attached-resources' => 'Activo en uso. Desvincúlalo antes de eliminarlo',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Eliminación masiva realizada exitosamente.',
                    'files-upload-success'                => 'Archivos subidos exitosamente.',
                    'file-upload-success'                 => 'Archivo subido exitosamente.',
                    'not-found'                           => 'Archivo no encontrado',
                    'edit-success'                        => 'Archivo subido exitosamente',
                    'show-success'                        => 'Archivo encontrado exitosamente',
                    'update-success'                      => 'Archivo actualizado exitosamente',
                    'not-found-to-update'                 => 'El archivo no existe',
                    'not-found-to-destroy'                => 'El archivo no existe',
                    'files-upload-failed'                 => 'Falló la subida de los archivos.',
                    'file-upload-failed'                  => 'Falló la subida del archivo',
                    'invalid-file'                        => 'Archivo inválido proporcionado',
                    'invalid-file-format'                 => 'Formato inválido',
                    'invalid-file-format-or-not-provided' => 'No se proporcionaron archivos o el formato es inválido.',
                    'download-image-failed'               => 'Falló la descarga de la imagen desde la URL',
                    'file-process-failed'                 => 'Algunos archivos no pudieron ser procesados',
                    'file-forbidden-type'                 => 'El archivo tiene un tipo o extensión prohibida.',
                    'file-too-large'                      => 'El archivo es demasiado grande. El tamaño máximo permitido es :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Activo',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Activo',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Activo',
            'property'         => 'Propiedad',
            'comment'          => 'Comentario',
            'linked_resources' => 'Recursos vinculados',
            'directory'        => 'Directorio',
            'tag'              => 'Etiqueta',
            'create'           => 'Crear',
            'edit'             => 'Editar',
            'update'           => 'Actualizar',
            'delete'           => 'Eliminar',
            'list'             => 'Listar',
            'view'             => 'Ver',
            'upload'           => 'Subir',
            're_upload'        => 'Volver a subir',
            'mass_update'      => 'Actualización masiva',
            'mass_delete'      => 'Eliminación masiva',
            'download'         => 'Descargar',
            'custom_download'  => 'Descarga personalizada',
            'rename'           => 'Cambiar nombre',
            'move'             => 'Mover',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar estructura del directorio',
            'download-zip'     => 'Descargar Zip',
            'asset-assign'     => 'Asignar activo',
        ],

        'validation' => [
            'asset' => [
                'required' => 'El campo :attribute es obligatorio.',
            ],

            'comment' => [
                'required' => 'El mensaje del comentario es obligatorio.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'El campo Etiqueta es obligatorio.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'El campo Nombre es obligatorio.',
                    'unique'   => 'El Nombre ya ha sido tomado.',
                ],
                'language' => [
                    'not-found' => 'El idioma seleccionado no pudo encontrarse o está deshabilitado actualmente.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Esta acción no está autorizada.',
        ],
    ],
];
