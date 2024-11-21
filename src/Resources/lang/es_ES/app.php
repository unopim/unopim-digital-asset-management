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
                    'add-asset'     => 'Añadir Activo',
                    'assign-assets' => 'Asignar Activos',
                    'assign'        => 'Asignar',
                    'preview-asset' => 'Previsualizar Activo',
                    'preview'       => 'Previsualizar',
                    'remove'        => 'Eliminar',
                    'download'      => 'Descargar',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM', // Consider using "Gestión de Activos Digitales"

                'datagrid' => [
                    'file-name'      => 'Nombre de Archivo',
                    'tags'           => 'Etiquetas',
                    'property-name'  => 'Nombre de la Propiedad',
                    'property-value' => 'Valor de la Propiedad',
                    'created-at'     => 'Creado en',
                    'updated-at'     => 'Actualizado en',
                    'extension'      => 'Extensión',
                    'path'           => 'Ruta',
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
                        'download-zip'              => 'Descargar ZIP',
                        'paste'                     => 'Pegar',
                        'add-directory'             => 'Añadir Directorio',
                        'upload-files'              => 'Subir Archivos',
                        'copy-directory-structured' => 'Copiar Estructura del Directorio',
                    ],

                    'not-found'                                 => 'No se encontró ningún directorio',
                    'created-success'                           => 'Directorio creado correctamente',
                    'updated-success'                           => 'Directorio actualizado correctamente',
                    'moved-success'                             => 'Directorio movido correctamente',
                    'can-not-deleted'                           => 'El directorio no se puede eliminar porque es el directorio raíz.',
                    'deleting-in-progress'                      => 'Eliminando directorio...',
                    'can-not-copy'                              => 'El directorio no se puede copiar porque es el directorio raíz.',
                    'coping-in-progress'                        => 'Copiando la estructura del directorio...',
                    'asset-not-found'                           => 'No se encontró ningún activo',
                    'asset-renamed-success'                     => 'Activo renombrado correctamente',
                    'asset-moved-success'                       => 'Activo movido correctamente',
                    'asset-name-already-exist'                  => 'El nuevo nombre ya existe con otro activo llamado :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'El nombre del activo entra en conflicto con un archivo existente en el mismo directorio.',
                    'old-file-not-found'                        => 'No se encontró el archivo solicitado en la ruta :old_path.',
                    'image-name-is-the-same'                    => 'Este nombre ya existe. Por favor, introduzca uno diferente.',
                    'not-writable'                              => 'No tiene permiso para :actionType un :type en esta ubicación ":path".',
                    'empty-directory'                           => 'Este directorio está vacío.',
                    'failed-download-directory'                 => 'Error al crear el archivo ZIP.',

                ],

                'title'       => 'DAM',
                'description' => 'Esta herramienta puede ayudarle a organizar, almacenar y gestionar todos sus activos multimedia en un solo lugar.',
                'root'        => 'Raíz',
                'upload'      => 'Subir',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propiedades del Activo',
                        'create-btn' => 'Crear Propiedad',

                        'datagrid' => [
                            'name'     => 'Nombre',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'edit'     => 'Editar',
                            'delete'   => 'Eliminar',
                        ],

                        'create' => [
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
                        'delete-success' => 'Propiedad del activo eliminada correctamente',
                        'create-success' => 'Propiedad del activo creada correctamente',
                        'update-success' => 'Propiedad del activo actualizada correctamente',
                    ],
                ],
                'comments' => [
                    'index'        => 'Añadir Comentario',
                    'create'       => [
                        'create-success' => 'El comentario se ha añadido correctamente.',
                    ],
                    'post-comment' => 'Publicar Comentario',
                    'post-reply'   => 'Publicar Respuesta',
                    'reply'        => 'Responder',
                    'add-reply'    => 'Añadir Respuesta',
                    'add-comment'  => 'Añadir Comentario',
                    'no-comments'  => 'Aún no hay comentarios',

                ],
                'edit' => [
                    'title'              => 'Editar Activo',
                    'name'               => 'Nombre',
                    'value'              => 'Valor',
                    'back-btn'           => 'Volver',
                    'save-btn'           => 'Guardar',
                    'embedded_meta_info' => 'Metadatos Integrados',
                    'custom_meta_info'   => 'Metadatos Personalizados',
                    'tags'               => 'Etiquetas',
                    'select-tags'        => 'Elegir o Crear una Etiqueta',
                    'tag'                => 'Etiqueta',
                    'directory-path'     => 'Ruta del Directorio',
                    'add_tags'           => 'Añadir Etiquetas',
                    'tab'                => [
                        'preview'          => 'Vista Previa',
                        'properties'       => 'Propiedades',
                        'comments'         => 'Comentarios',
                        'linked_resources' => 'Recursos Vinculados',
                        'history'          => 'Historial',
                        'metadata'         => 'Metadatos',
                    ],
                    'button' => [
                        'download'        => 'Descargar',
                        'custom_download' => 'Descarga Personalizada',
                        'rename'          => 'Renombrar',
                        're_upload'       => 'Volver a Subir',
                        'delete'          => 'Eliminar',
                    ],

                    'custom-download' => [
                        'title'              => 'Descarga Personalizada',
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
                    'image-source-not-readable' => 'El origen de la imagen no es legible',
                    'failed-to-read'            => 'Error al leer los metadatos de la imagen :exception',
                    'file_re_upload_success'    => 'Archivos vueltos a subir correctamente.',

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
                            'resource-view' => 'Vista de Recurso',
                        ],
                    ],
                ],
                'delete-success'                          => 'Activo eliminado correctamente',
                'delete-failed-due-to-attached-resources' => 'No se pudo eliminar el activo porque está vinculado a recursos (Nombre del Activo: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Eliminación masiva realizada correctamente.',
                    'files_upload_success' => 'Archivos subidos correctamente.',
                    'file_upload_success'  => 'Archivo subido correctamente.',
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
            'menu'             => 'DAM',  // Consider "Gestión de Activos Digitales"
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
            're_upload'        => 'Volver a Subir',
            'mass_update'      => 'Actualización Masiva',
            'mass_delete'      => 'Eliminación Masiva',
            'download'         => 'Descargar',
            'custom_download'  => 'Descarga Personalizada',
            'rename'           => 'Renombrar',
            'move'             => 'Mover',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar Estructura del Directorio',
            'download-zip'     => 'Descargar ZIP',
            'asset-assign'     => 'Asignar Activo',
        ],

        'validation' => [
            'asset' => [
                'required' => 'El campo :attribute es obligatorio.',
            ],

            'comment' => [
                'required' => 'El mensaje del comentario es obligatorio.',
            ],

            'property' => [
                'name' => [
                    'required' => 'El campo Nombre es obligatorio.',
                    'unique'   => 'El nombre ya está en uso.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Esta acción no está autorizada.',
        ],
    ],
];
