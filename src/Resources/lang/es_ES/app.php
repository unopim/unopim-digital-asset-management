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
                    'message' => 'Eliminar este directorio también eliminará todos los subdirectorios dentro de él. Esta acción es permanente y no se puede deshacer.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Añadir activo',
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
                    'created-at'     => 'Creado en',
                    'updated-at'     => 'Actualizado en',
                    'extension'      => 'Extensión',
                    'path'           => 'Ruta',
                    'size'           => 'Tamaño',
                ],

                'directory' => [
                    'title'        => 'Directorio',
                    'create'       => [
                        'title'    => 'Crear Directorio',
                        'name'     => 'Nombre',
                        'save-btn' => 'Guardar Directorio',
                    ],

                    'rename' => [
                        'title' => 'Renombrar Directorio',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Renombrar Activo',
                            'save-btn' => 'Guardar Activo',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Eliminar',
                        'rename'                    => 'Renombrar',
                        'copy'                      => 'Copiar',
                        'download'                  => 'Descargar',
                        'download-zip'              => 'Descargar Zip',
                        'paste'                     => 'Pegar',
                        'add-directory'             => 'Añadir Directorio',
                        'upload-files'              => 'Subir Archivos',
                        'copy-directory-structured' => 'Copiar Estructura de Directorio',
                    ],

                    'not-found'                                 => 'No se encontró el directorio',
                    'created-success'                           => 'Directorio creado con éxito',
                    'updated-success'                           => 'Directorio actualizado con éxito',
                    'moved-success'                             => 'Directorio movido con éxito',
                    'fetch-all-success'                         => 'Directorios obtenidos con éxito',
                    'can-not-deleted'                           => 'No se puede eliminar el directorio ya que es el directorio raíz.',
                    'deleting-in-progress'                      => 'Eliminación de directorio en progreso',
                    'can-not-copy'                              => 'No se puede copiar el directorio ya que es el directorio raíz.',
                    'coping-in-progress'                        => 'Estructura de directorio en progreso de copiado.',
                    'asset-not-found'                           => 'No se encontró el activo',
                    'asset-renamed-success'                     => 'Activo renombrado con éxito',
                    'asset-moved-success'                       => 'Activo movido con éxito',
                    'asset-name-already-exist'                  => 'El nuevo nombre ya existe con otro activo llamado :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'El nombre del activo entra en conflicto con un archivo existente en el mismo directorio.',
                    'old-file-not-found'                        => 'No se encontró el archivo solicitado en la ruta :old_path.',
                    'image-name-is-the-same'                    => 'Este nombre ya existe. Por favor, ingrese otro.',
                    'not-writable'                              => 'No tienes permiso para :actionType un :type en esta ubicación ":path".',
                    'empty-directory'                           => 'Este directorio está vacío.',
                    'failed-download-directory'                 => 'No se pudo crear el archivo zip.',
                    'not-allowed'                               => 'No se permite la carga de archivos de script.',
                ],

                'title'       => 'DAM',
                'description' => 'Esta herramienta te ayudará a organizar, almacenar y gestionar todos tus activos multimedia en un solo lugar',
                'root'        => 'Raíz',
                'upload'      => 'Subir',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propiedades del Activo',
                        'create-btn' => 'Crear Propiedad',

                        'datagrid'      => [
                            'name'     => 'Nombre',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'edit'     => 'Editar',
                            'delete'   => 'Eliminar',
                        ],

                        'create'     => [
                            'title'    => 'Crear Propiedad',
                            'name'     => 'Nombre',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'save-btn' => 'Guardar',
                        ],
                        'edit' => [
                            'title' => 'Editar Propiedad',
                        ],
                        'delete-success' => 'Propiedad del Activo eliminada con éxito',
                        'create-success' => 'Propiedad del Activo creada con éxito',
                        'update-success' => 'Propiedad del Activo actualizada con éxito',
                        'not-found'      => 'Propiedad no encontrada',
                        'found-success'  => 'Propiedad encontrada con éxito',

                    ],
                ],
                'comments' => [
                    'index'  => 'Añadir Comentario',
                    'create' => [
                        'create-success' => 'Comentario añadido con éxito',
                        'create-failure' => 'Error al crear comentario',
                    ],
                    'post-comment'    => 'Publicar Comentario',
                    'post-reply'      => 'Publicar Respuesta',
                    'reply'           => 'Responder',
                    'add-reply'       => 'Añadir Respuesta',
                    'add-comment'     => 'Añadir Comentario',
                    'no-comments'     => 'Aún no hay comentarios',
                    'not-found'       => 'Comentarios no encontrados',
                    'updated-success' => 'Comentario actualizado con éxito',
                    'update-failed'   => 'Error al actualizar el comentario',
                    'delete-success'  => 'Comentario del Activo eliminado con éxito',
                    'delete-failed'   => 'Error al eliminar el comentario del Activo',

                ],
                'edit' => [
                    'title'              => 'Editar Activo',
                    'name'               => 'Nombre',
                    'value'              => 'Valor',
                    'back-btn'           => 'Volver',
                    'save-btn'           => 'Guardar',
                    'embedded_meta_info' => 'Información Meta Integrada',
                    'custom_meta_info'   => 'Información Meta Personalizada',
                    'tags'               => 'Etiquetas',
                    'select-tags'        => 'Elegir o Crear una Etiqueta',
                    'tag'                => 'Etiqueta',
                    'directory-path'     => 'Ruta del Directorio',
                    'add_tags'           => 'Añadir Etiquetas',
                    'tab'                => [
                        'preview'          => 'Vista previa',
                        'properties'       => 'Propiedades',
                        'comments'         => 'Comentarios',
                        'linked_resources' => 'Recursos Vinculados',
                        'history'          => 'Historial',
                    ],
                    'button' => [
                        'download'        => 'Descargar',
                        'custom_download' => 'Descarga Personalizada',
                        'rename'          => 'Renombrar',
                        're_upload'       => 'Subir de nuevo',
                        'delete'          => 'Eliminar',
                    ],

                    'custom-download' => [
                        'title'              => 'Descarga Personalizada',
                        'format'             => 'Formato',
                        'width'              => 'Ancho (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Altura (px)',
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
                    'failed-to-read'            => 'No se pudo leer los metadatos de la imagen :exception',
                    'file-re-upload-success'    => 'Archivos subidos nuevamente con éxito.',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Producto',
                            'category'      => 'Categoría',
                            'product-sku'   => 'SKU del Producto: ',
                            'category code' => 'Código de Categoría: ',
                            'resource-type' => 'Tipo de Recurso',
                            'resource'      => 'Recurso',
                            'resource-view' => 'Vista del Recurso',
                        ],
                    ],
                    'found-success' => 'Recurso encontrado con éxito',
                    'not-found'     => 'Recurso no encontrado',
                ],
                'tags' => [
                    'index'  => 'Añadir etiquetas',
                    'create' => [
                        'create-success' => 'Las etiquetas se han añadido con éxito',
                        'create-failure' => 'Error al crear las etiquetas',
                    ],

                    'no-comments'    => 'Aún no hay etiquetas',
                    'found-success'  => 'Etiqueta encontrada con éxito',
                    'not-found'      => 'Etiqueta no encontrada',
                    'update-success' => 'Etiquetas actualizadas con éxito',
                    'update-failed'  => 'Error al actualizar las etiquetas',
                    'delete-success' => 'Etiquetas del recurso eliminadas con éxito',
                    'delete-failed'  => 'Error al eliminar las etiquetas del activo',
                ],
                'delete-success'                          => 'Activo eliminado con éxito',
                'delete-failed-due-to-attached-resources' => 'El recurso está en uso. Desvincúlalo antes de eliminarlo',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Eliminación masiva realizada con éxito.',
                    'files-upload-success'                => 'Archivos subidos con éxito.',
                    'file-upload-success'                 => 'Archivo subido con éxito.',
                    'not-found'                           => 'Archivo no encontrado',
                    'edit-success'                        => 'Archivo editado con éxito',
                    'show-success'                        => 'Archivo encontrado con éxito',
                    'update-success'                      => 'Archivo actualizado con éxito',
                    'not-found-to-update'                 => 'El archivo no existe',
                    'not-found-to-destroy'                => 'El archivo no existe',
                    'files-upload-failed'                 => 'Error al subir los archivos.',
                    'file-upload-failed'                  => 'Error al subir el archivo',
                    'invalid-file'                        => 'Archivo no válido proporcionado',
                    'invalid-file-format'                 => 'Formato no válido',
                    'invalid-file-format-or-not-provided' => 'No se proporcionaron archivos o el formato es incorrecto.',
                    'download-image-failed'               => 'Error al descargar la imagen desde la URL',
                    'file-process-failed'                 => 'Algunos archivos no se pudieron procesar',
                    'file-forbidden-type'                 => 'El archivo tiene un tipo o extensión prohibida.',
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
            'linked_resources' => 'Recursos Vinculados',
            'directory'        => 'Directorio',
            'tag'              => 'Etiqueta',
            'create'           => 'Crear',
            'edit'             => 'Editar',
            'update'           => 'Actualizar',
            'delete'           => 'Eliminar',
            'list'             => 'Lista',
            'view'             => 'Ver',
            'upload'           => 'Subir',
            're_upload'        => 'Subir de nuevo',
            'mass_update'      => 'Actualización masiva',
            'mass_delete'      => 'Eliminación masiva',
            'download'         => 'Descargar',
            'custom_download'  => 'Descarga personalizada',
            'rename'           => 'Renombrar',
            'move'             => 'Mover',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar estructura de directorio',
            'download-zip'     => 'Descargar ZIP',
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
                    'not-found' => 'El idioma seleccionado no se encontró o está deshabilitado actualmente.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Esta acción no está autorizada.',
        ],
    ],
];
