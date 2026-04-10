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
                    'message' => 'Eliminar este diretório também eliminará todos os subdiretórios no seu interior. Esta ação é permanente e não pode ser anulada.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Adicionar Ativo',
                    'assign-assets' => 'Atribuir Ativos',
                    'assign'        => 'Atribuir',
                    'preview-asset' => 'Pré-visualizar Ativo',
                    'preview'       => 'Pré-visualizar',
                    'remove'        => 'Remover',
                    'download'      => 'Transferir',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nome do Ficheiro',
                    'tags'           => 'Etiquetas',
                    'property-name'  => 'Nome da Propriedade',
                    'property-value' => 'Valor da Propriedade',
                    'created-at'     => 'Criado em',
                    'updated-at'     => 'Atualizado em',
                    'extension'      => 'Extensão',
                    'path'           => 'Caminho',
                    'size'           => 'Tamanho',
                ],

                'directory' => [
                    'title'        => 'Diretório',
                    'create'       => [
                        'title'    => 'Criar Diretório',
                        'name'     => 'Nome',
                        'save-btn' => 'Guardar Diretório',
                    ],

                    'rename' => [
                        'title' => 'Renomear Diretório',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Renomear Ativo',
                            'save-btn' => 'Guardar Ativo',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Eliminar',
                        'rename'                    => 'Renomear',
                        'copy'                      => 'Copiar',
                        'download'                  => 'Transferir',
                        'download-zip'              => 'Transferir Zip',
                        'paste'                     => 'Colar',
                        'add-directory'             => 'Adicionar Diretório',
                        'upload-files'              => 'Carregar Ficheiros',
                        'copy-directory-structured' => 'Copiar Estrutura do Diretório',
                        'get-by-id'                 => 'Obter por Id',
                        'comment'                   => 'Comentário',
                    ],

                    'linked-resources'                          => 'Recursos Associados',
                    'not-found'                                 => 'Nenhum diretório encontrado',
                    'created-success'                           => 'Diretório criado com sucesso',
                    'updated-success'                           => 'Diretório atualizado com sucesso',
                    'moved-success'                             => 'Diretório movido com sucesso',
                    'fetch-all-success'                         => 'Diretórios obtidos com sucesso',
                    'can-not-deleted'                           => 'O diretório não pode ser eliminado pois é o Diretório Raiz.',
                    'deleting-in-progress'                      => 'Eliminação do diretório em curso',
                    'can-not-copy'                              => 'O diretório não pode ser copiado pois é o Diretório Raiz.',
                    'coping-in-progress'                        => 'Cópia da estrutura do diretório em curso.',
                    'asset-not-found'                           => 'Nenhum ativo encontrado',
                    'asset-renamed-success'                     => 'Ativo renomeado com sucesso',
                    'asset-moved-success'                       => 'Ativo movido com sucesso',
                    'asset-name-already-exist'                  => 'O novo nome já existe com outro ativo chamado :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'O nome do ativo entra em conflito com um ficheiro existente no mesmo diretório.',
                    'old-file-not-found'                        => 'O ficheiro solicitado no caminho :old_path não foi encontrado.',
                    'image-name-is-the-same'                    => 'Este nome já existe. Por favor, introduza um nome diferente.',
                    'not-writable'                              => 'Não tem permissão para :actionType um :type nesta localização ":path".',
                    'empty-directory'                           => 'Este diretório está vazio.',
                    'failed-download-directory'                 => 'Falha ao criar o ficheiro zip.',
                    'not-allowed'                               => 'O carregamento de ficheiros de script não é permitido.',
                ],

                'title'       => 'DAM',
                'description' => 'A ferramenta pode ajudá-lo a organizar, armazenar e gerir todos os seus ativos de multimédia num só local',
                'root'        => 'Raiz',
                'upload'      => 'Carregar',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Propriedades do Ativo',
                        'create-btn' => 'Criar Propriedade',

                        'datagrid'      => [
                            'name'     => 'Nome',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'edit'     => 'Editar',
                            'delete'   => 'Eliminar',
                        ],

                        'create'     => [
                            'title'    => 'Criar Propriedade',
                            'name'     => 'Nome',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'save-btn' => 'Guardar',
                        ],
                        'edit' => [
                            'title' => 'Editar Propriedade',
                        ],
                        'delete-success' => 'Propriedade do Ativo eliminada com sucesso',
                        'create-success' => 'Propriedade do Ativo criada com sucesso',
                        'update-success' => 'Propriedade do Ativo atualizada com sucesso',
                        'not-found'      => 'Propriedade não encontrada',
                        'found-success'  => 'Propriedade encontrada com sucesso',
                    ],
                ],
                'comments' => [
                    'index'  => 'Adicionar Comentário',
                    'create' => [
                        'create-success' => 'Comentário adicionado com sucesso',
                        'create-failure' => 'Falha ao criar comentário',
                    ],
                    'post-comment'    => 'Publicar Comentário',
                    'post-reply'      => 'Publicar Resposta',
                    'reply'           => 'Responder',
                    'add-reply'       => 'Adicionar Resposta',
                    'add-comment'     => 'Adicionar Comentário',
                    'no-comments'     => 'Ainda sem comentários',
                    'not-found'       => 'Comentários não encontrados',
                    'updated-success' => 'Comentário atualizado com sucesso',
                    'update-failed'   => 'Falha ao atualizar comentário',
                    'delete-success'  => 'Comentário do Ativo eliminado com sucesso',
                    'delete-failed'   => 'Falha ao eliminar comentário do Ativo',
                ],
                'edit' => [
                    'title'              => 'Editar Ativo',
                    'name'               => 'Nome',
                    'value'              => 'Valor',
                    'back-btn'           => 'Voltar',
                    'save-btn'           => 'Guardar',
                    'embedded_meta_info' => 'Informações Meta Incorporadas',
                    'custom_meta_info'   => 'Informações Meta Personalizadas',
                    'tags'               => 'Etiquetas',
                    'select-tags'        => 'Escolher ou Criar uma Etiqueta',
                    'tag'                => 'Etiqueta',
                    'directory-path'     => 'Caminho do Diretório',
                    'add_tags'           => 'Adicionar Etiquetas',
                    'tab'                => [
                        'preview'          => 'Pré-visualização',
                        'properties'       => 'Propriedades',
                        'comments'         => 'Comentários',
                        'linked_resources' => 'Recursos Associados',
                        'history'          => 'Histórico',
                    ],
                    'button' => [
                        'download'        => 'Transferir',
                        'custom_download' => 'Transferência Personalizada',
                        'rename'          => 'Renomear',
                        're_upload'       => 'Recarregar',
                        'delete'          => 'Eliminar',
                    ],

                    'custom-download' => [
                        'title'              => 'Transferência Personalizada',
                        'format'             => 'Formato',
                        'width'              => 'Largura (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Altura (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Transferir',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'A etiqueta já existe',
                    'image-source-not-readable' => 'Origem da imagem não legível',
                    'failed-to-read'            => 'Falha ao ler os metadados da imagem :exception',
                    'file-re-upload-success'    => 'Ficheiros recarregados com sucesso.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produto',
                            'category'      => 'Categoria',
                            'product-sku'   => 'Sku do Produto: ',
                            'category code' => 'Código da Categoria: ',
                            'resource-type' => 'Tipo de Recurso',
                            'resource'      => 'Recurso',
                            'resource-view' => 'Vista do Recurso',
                        ],
                    ],
                    'found-success' => 'Recurso encontrado com sucesso',
                    'not-found'     => 'Recurso não encontrado',
                ],
                'tags' => [
                    'index'  => 'Adicionar etiquetas',
                    'create' => [
                        'create-success' => 'As etiquetas foram adicionadas com sucesso',
                        'create-failure' => 'Falha ao criar etiquetas',
                    ],

                    'no-comments'    => 'Ainda sem etiquetas',
                    'found-success'  => 'Etiqueta encontrada com sucesso',
                    'not-found'      => 'Etiquetas não encontradas',
                    'update-success' => 'Etiquetas atualizadas com sucesso',
                    'update-failed'  => 'Falha ao atualizar etiquetas',
                    'delete-success' => 'Etiquetas do Ativo removidas com sucesso',
                    'delete-failed'  => 'Falha ao eliminar etiquetas do Ativo',
                ],
                'delete-success'                          => 'Ativo eliminado com sucesso',
                'delete-failed-due-to-attached-resources' => 'Ativo em utilização. Desassocie antes de eliminar',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Eliminação em massa realizada com sucesso.',
                    'files-upload-success'                => 'Ficheiros carregados com sucesso.',
                    'file-upload-success'                 => 'Ficheiro carregado com sucesso.',
                    'not-found'                           => 'Ficheiro não encontrado',
                    'edit-success'                        => 'Ficheiro carregado com sucesso',
                    'show-success'                        => 'Ficheiro encontrado com sucesso',
                    'update-success'                      => 'Ficheiro atualizado com sucesso',
                    'not-found-to-update'                 => 'O ficheiro não existe',
                    'not-found-to-destroy'                => 'O ficheiro não existe',
                    'files-upload-failed'                 => 'Falha ao carregar ficheiros.',
                    'file-upload-failed'                  => 'Falha ao carregar ficheiro',
                    'invalid-file'                        => 'Ficheiro inválido fornecido',
                    'invalid-file-format'                 => 'Formato inválido',
                    'invalid-file-format-or-not-provided' => 'Nenhum ficheiro fornecido ou formato inválido.',
                    'download-image-failed'               => 'Falha ao transferir imagem a partir do URL',
                    'file-process-failed'                 => 'Falha no processamento de alguns ficheiros',
                    'file-forbidden-type'                 => 'O ficheiro possui um tipo ou extensão proibida.',
                    'file-too-large'                      => 'O ficheiro é demasiado grande. O tamanho máximo permitido é :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Ativo',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Ativo',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Ativo',
            'property'         => 'Propriedade',
            'comment'          => 'Comentário',
            'linked_resources' => 'Recursos Associados',
            'directory'        => 'Diretório',
            'tag'              => 'Etiqueta',
            'create'           => 'Criar',
            'edit'             => 'Editar',
            'update'           => 'Atualizar',
            'delete'           => 'Eliminar',
            'list'             => 'Listar',
            'view'             => 'Ver',
            'upload'           => 'Carregar',
            're_upload'        => 'Recarregar',
            'mass_update'      => 'Atualização em Massa',
            'mass_delete'      => 'Eliminação em Massa',
            'download'         => 'Transferir',
            'custom_download'  => 'Transferência Personalizada',
            'rename'           => 'Renomear',
            'move'             => 'Mover',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar Estrutura do Diretório',
            'download-zip'     => 'Transferir Zip',
            'asset-assign'     => 'Atribuir Ativo',
        ],

        'validation' => [
            'asset' => [
                'required' => 'O campo :attribute é obrigatório.',
            ],

            'comment' => [
                'required' => 'A mensagem do comentário é obrigatória.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'O campo Etiqueta é obrigatório.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'O campo Nome é obrigatório.',
                    'unique'   => 'O Nome já está a ser utilizado.',
                ],
                'language' => [
                    'not-found' => 'O idioma selecionado não foi encontrado ou está atualmente desativado.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Esta ação não está autorizada.',
        ],
    ],
];
