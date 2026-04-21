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
                    'message' => 'Excluir este diretório também excluirá todos os subdiretórios dentro dele. Esta ação é permanente e não pode ser desfeita.',
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
                    'download'      => 'Baixar',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nome do Arquivo',
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
                        'save-btn' => 'Salvar Diretório',
                    ],

                    'rename' => [
                        'title' => 'Renomear Diretório',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Renomear Ativo',
                            'save-btn' => 'Salvar Ativo',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Excluir',
                        'rename'                    => 'Renomear',
                        'copy'                      => 'Copiar',
                        'download'                  => 'Baixar',
                        'download-zip'              => 'Baixar Zip',
                        'paste'                     => 'Colar',
                        'add-directory'             => 'Adicionar Diretório',
                        'upload-files'              => 'Enviar Arquivos',
                        'copy-directory-structured' => 'Copiar Estrutura do Diretório',
                        'get-by-id'                 => 'Obter por Id',
                        'comment'                   => 'Comentário',
                    ],

                    'linked-resources'                          => 'Recursos Vinculados',
                    'not-found'                                 => 'Nenhum diretório encontrado',
                    'created-success'                           => 'Diretório criado com sucesso',
                    'updated-success'                           => 'Diretório atualizado com sucesso',
                    'moved-success'                             => 'Diretório movido com sucesso',
                    'fetch-all-success'                         => 'Diretórios obtidos com sucesso',
                    'can-not-deleted'                           => 'O diretório não pode ser excluído pois é o Diretório Raiz.',
                    'deleting-in-progress'                      => 'Exclusão do diretório em andamento',
                    'can-not-copy'                              => 'O diretório não pode ser copiado pois é o Diretório Raiz.',
                    'coping-in-progress'                        => 'Cópia da estrutura do diretório em andamento.',
                    'asset-not-found'                           => 'Nenhum ativo encontrado',
                    'asset-renamed-success'                     => 'Ativo renomeado com sucesso',
                    'asset-moved-success'                       => 'Ativo movido com sucesso',
                    'asset-name-already-exist'                  => 'O novo nome já existe com outro ativo chamado :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'O nome do ativo entra em conflito com um arquivo existente no mesmo diretório.',
                    'old-file-not-found'                        => 'O arquivo solicitado no caminho :old_path não foi encontrado.',
                    'image-name-is-the-same'                    => 'Este nome já existe. Por favor, insira um nome diferente.',
                    'not-writable'                              => 'Você não tem permissão para :actionType um :type neste local ":path".',
                    'empty-directory'                           => 'Este diretório está vazio.',
                    'failed-download-directory'                 => 'Falha ao criar o arquivo zip.',
                    'not-allowed'                               => 'O envio de arquivos de script não é permitido.',
                ],

                'title'       => 'DAM',
                'description' => 'A ferramenta pode ajudá-lo a organizar, armazenar e gerenciar todos os seus ativos de mídia em um único lugar',
                'root'        => 'Raiz',
                'upload'      => 'Enviar',
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
                            'delete'   => 'Excluir',
                        ],

                        'create'     => [
                            'title'    => 'Criar Propriedade',
                            'name'     => 'Nome',
                            'type'     => 'Tipo',
                            'language' => 'Idioma',
                            'value'    => 'Valor',
                            'save-btn' => 'Salvar',
                        ],
                        'edit' => [
                            'title' => 'Editar Propriedade',
                        ],
                        'delete-success' => 'Propriedade do Ativo excluída com sucesso',
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
                    'no-comments'     => 'Ainda não há comentários',
                    'not-found'       => 'Comentários não encontrados',
                    'updated-success' => 'Comentário atualizado com sucesso',
                    'update-failed'   => 'Falha ao atualizar comentário',
                    'delete-success'  => 'Comentário do Ativo excluído com sucesso',
                    'delete-failed'   => 'Falha ao excluir comentário do Ativo',
                ],
                'edit' => [
                    'title'              => 'Editar Ativo',
                    'name'               => 'Nome',
                    'value'              => 'Valor',
                    'back-btn'           => 'Voltar',
                    'save-btn'           => 'Salvar',
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
                        'linked_resources' => 'Recursos Vinculados',
                        'history'          => 'Histórico',
                    ],
                    'button' => [
                        'download'        => 'Baixar',
                        'custom_download' => 'Download Personalizado',
                        'rename'          => 'Renomear',
                        're_upload'       => 'Reenviar',
                        'delete'          => 'Excluir',
                    ],

                    'custom-download' => [
                        'title'              => 'Download Personalizado',
                        'format'             => 'Formato',
                        'width'              => 'Largura (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Altura (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Baixar',

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
                    'file-re-upload-success'    => 'Arquivos reenviados com sucesso.',

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
                            'resource-view' => 'Visualização do Recurso',
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

                    'no-comments'    => 'Ainda não há etiquetas',
                    'found-success'  => 'Etiqueta encontrada com sucesso',
                    'not-found'      => 'Etiquetas não encontradas',
                    'update-success' => 'Etiquetas atualizadas com sucesso',
                    'update-failed'  => 'Falha ao atualizar etiquetas',
                    'delete-success' => 'Etiquetas do Ativo removidas com sucesso',
                    'delete-failed'  => 'Falha ao excluir etiquetas do Ativo',
                ],
                'delete-success'                          => 'Ativo excluído com sucesso',
                'delete-failed-due-to-attached-resources' => 'Ativo em uso. Desvincule antes de excluir',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Exclusão em massa realizada com sucesso.',
                    'files-upload-success'                => 'Arquivos enviados com sucesso.',
                    'file-upload-success'                 => 'Arquivo enviado com sucesso.',
                    'not-found'                           => 'Arquivo não encontrado',
                    'edit-success'                        => 'Arquivo enviado com sucesso',
                    'show-success'                        => 'Arquivo encontrado com sucesso',
                    'update-success'                      => 'Arquivo atualizado com sucesso',
                    'not-found-to-update'                 => 'O arquivo não existe',
                    'not-found-to-destroy'                => 'O arquivo não existe',
                    'files-upload-failed'                 => 'Falha ao enviar arquivos.',
                    'file-upload-failed'                  => 'Falha ao enviar arquivo',
                    'invalid-file'                        => 'Arquivo inválido fornecido',
                    'invalid-file-format'                 => 'Formato inválido',
                    'invalid-file-format-or-not-provided' => 'Nenhum arquivo fornecido ou formato inválido.',
                    'download-image-failed'               => 'Falha ao baixar imagem da URL',
                    'file-process-failed'                 => 'Falha ao processar alguns arquivos',
                    'file-forbidden-type'                 => 'O arquivo possui tipo ou extensão proibida.',
                    'file-too-large'                      => 'O arquivo é muito grande. O tamanho máximo permitido é :size.',
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
            'linked_resources' => 'Recursos Vinculados',
            'directory'        => 'Diretório',
            'tag'              => 'Etiqueta',
            'create'           => 'Criar',
            'edit'             => 'Editar',
            'update'           => 'Atualizar',
            'delete'           => 'Excluir',
            'list'             => 'Listar',
            'view'             => 'Visualizar',
            'upload'           => 'Enviar',
            're_upload'        => 'Reenviar',
            'mass_update'      => 'Atualização em Massa',
            'mass_delete'      => 'Exclusão em Massa',
            'download'         => 'Baixar',
            'custom_download'  => 'Download Personalizado',
            'rename'           => 'Renomear',
            'move'             => 'Mover',
            'copy'             => 'Copiar',
            'copy-structure'   => 'Copiar Estrutura do Diretório',
            'download-zip'     => 'Baixar Zip',
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
                    'unique'   => 'O Nome já está em uso.',
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
