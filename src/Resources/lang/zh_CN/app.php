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
                    'message' => '删除此目录将同时删除其中的所有子目录。此操作是永久性的，无法撤销。',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => '添加资产',
                    'assign-assets' => '分配资产',
                    'assign'        => '分配',
                    'preview-asset' => '预览资产',
                    'preview'       => '预览',
                    'remove'        => '移除',
                    'download'      => '下载',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => '文件名',
                    'tags'           => '标签',
                    'property-name'  => '属性名称',
                    'property-value' => '属性值',
                    'created-at'     => '创建时间',
                    'updated-at'     => '更新时间',
                    'extension'      => '扩展名',
                    'path'           => '路径',
                    'size'           => '大小',
                ],

                'directory' => [
                    'title'        => '目录',
                    'create'       => [
                        'title'    => '创建目录',
                        'name'     => '名称',
                        'save-btn' => '保存目录',
                    ],

                    'rename' => [
                        'title' => '重命名目录',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => '重命名资产',
                            'save-btn' => '保存资产',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => '删除',
                        'rename'                    => '重命名',
                        'copy'                      => '复制',
                        'download'                  => '下载',
                        'download-zip'              => '下载 Zip',
                        'paste'                     => '粘贴',
                        'add-directory'             => '添加目录',
                        'upload-files'              => '上传文件',
                        'copy-directory-structured' => '复制目录结构',
                    ],

                    'not-found'                                 => '未找到目录',
                    'created-success'                           => '目录创建成功',
                    'updated-success'                           => '目录更新成功',
                    'moved-success'                             => '目录移动成功',
                    'fetch-all-success'                         => '目录获取成功',
                    'can-not-deleted'                           => '不能删除根目录。',
                    'deleting-in-progress'                      => '目录删除中',
                    'can-not-copy'                              => '根目录不能被复制。',
                    'coping-in-progress'                        => '目录结构复制中。',
                    'asset-not-found'                           => '未找到资产',
                    'asset-renamed-success'                     => '资产重命名成功',
                    'asset-moved-success'                       => '资产移动成功',
                    'asset-name-already-exist'                  => '新名称已存在，另有一个资产名为 :asset_name',
                    'asset-name-conflict-in-the-same-directory' => '资产名称与同一目录中的现有文件冲突。',
                    'old-file-not-found'                        => '在路径 :old_path 请求的文件未找到。',
                    'image-name-is-the-same'                    => '该名称已存在，请输入一个不同的名称。',
                    'not-writable'                              => '您没有权限在此位置 ":path" 执行 :actionType 操作。',
                    'empty-directory'                           => '此目录为空。',
                    'failed-download-directory'                 => '无法创建 zip 文件。',
                    'not-allowed'                               => '不允许上传脚本文件。',
                ],

                'title'       => 'DAM',
                'description' => '该工具可以帮助您在一个地方组织、存储和管理所有的媒体资产。',
                'root'        => '根目录',
                'upload'      => '上传',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => '资产属性',
                        'create-btn' => '创建属性',

                        'datagrid'      => [
                            'name'     => '名称',
                            'type'     => '类型',
                            'language' => '语言',
                            'value'    => '值',
                            'edit'     => '编辑',
                            'delete'   => '删除',
                        ],

                        'create'     => [
                            'title'    => '创建属性',
                            'name'     => '名称',
                            'type'     => '类型',
                            'language' => '语言',
                            'value'    => '值',
                            'save-btn' => '保存',
                        ],
                        'edit' => [
                            'title' => '编辑属性',
                        ],
                        'delete-success' => '资产属性删除成功',
                        'create-success' => '资产属性创建成功',
                        'update-success' => '资产属性更新成功',
                        'not-found'      => '未找到属性',
                        'found-success'  => '属性查找成功',

                    ],
                ],
                'comments' => [
                    'index'  => '添加评论',
                    'create' => [
                        'create-success' => '评论已成功添加',
                        'create-failure' => '评论创建失败',
                    ],
                    'post-comment'    => '发布评论',
                    'post-reply'      => '发布回复',
                    'reply'           => '回复',
                    'add-reply'       => '添加回复',
                    'add-comment'     => '添加评论',
                    'no-comments'     => '暂无评论',
                    'not-found'       => '未找到评论',
                    'updated-success' => '评论更新成功',
                    'update-failed'   => '评论更新失败',
                    'delete-success'  => '资产评论删除成功',
                    'delete-failed'   => '资产评论删除失败',
                ],

                'edit' => [
                    'title'              => '编辑资产',
                    'name'               => '名称',
                    'value'              => '值',
                    'back-btn'           => '返回',
                    'save-btn'           => '保存',
                    'embedded_meta_info' => '嵌入的元数据',
                    'custom_meta_info'   => '自定义元数据',
                    'tags'               => '标签',
                    'select-tags'        => '选择或创建标签',
                    'tag'                => '标签',
                    'directory-path'     => '目录路径',
                    'add_tags'           => '添加标签',
                    'tab'                => [
                        'preview'          => '预览',
                        'properties'       => '属性',
                        'comments'         => '评论',
                        'linked_resources' => '关联资源',
                        'history'          => '历史',
                    ],
                    'button' => [
                        'download'        => '下载',
                        'custom_download' => '自定义下载',
                        'rename'          => '重命名',
                        're_upload'       => '重新上传',
                        'delete'          => '删除',
                    ],

                    'custom-download' => [
                        'title'              => '自定义下载',
                        'format'             => '格式',
                        'width'              => '宽度（px）',
                        'width-placeholder'  => '200',
                        'height'             => '高度（px）',
                        'height-placeholder' => '200',
                        'download-btn'       => '下载',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => '原始',
                        ],
                    ],

                    'tag-already-exists'        => '标签已存在',
                    'image-source-not-readable' => '无法读取图像源',
                    'failed-to-read'            => '无法读取图像元数据 :exception',
                    'file-re-upload-success'    => '文件重新上传成功。',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '产品',
                            'category'      => '分类',
                            'product-sku'   => '产品SKU：',
                            'category code' => '分类代码：',
                            'resource-type' => '资源类型',
                            'resource'      => '资源',
                            'resource-view' => '资源视图',
                        ],
                    ],
                    'found-success' => '资源找到成功',
                    'not-found'     => '未找到资源',
                ],
                'tags' => [
                    'index'  => '添加标签',
                    'create' => [
                        'create-success' => '标签已成功添加',
                        'create-failure' => '标签创建失败',
                    ],

                    'no-comments'    => '尚无标签',
                    'found-success'  => '标签找到成功',
                    'not-found'      => '未找到标签',
                    'update-success' => '标签更新成功',
                    'update-failed'  => '标签更新失败',
                    'delete-success' => '资产标签已成功移除',
                    'delete-failed'  => '资产标签删除失败',
                ],
                'delete-success'                          => '资产删除成功',
                'delete-failed-due-to-attached-resources' => '资源正在使用中。删除前请先取消关联',
                'datagrid'                                => [
                    'mass-delete-success'                 => '批量删除成功。',
                    'files-upload-success'                => '文件上传成功。',
                    'file-upload-success'                 => '文件上传成功。',
                    'not-found'                           => '未找到文件',
                    'edit-success'                        => '文件上传成功',
                    'show-success'                        => '文件找到成功',
                    'update-success'                      => '文件更新成功',
                    'not-found-to-update'                 => '文件不存在',
                    'not-found-to-destroy'                => '文件不存在',
                    'files-upload-failed'                 => '文件上传失败。',
                    'file-upload-failed'                  => '文件上传失败',
                    'invalid-file'                        => '提供的文件无效',
                    'invalid-file-format'                 => '格式无效',
                    'invalid-file-format-or-not-provided' => '没有提供文件或格式无效。',
                    'download-image-failed'               => '无法从URL下载图像',
                    'file-process-failed'                 => '一些文件处理失败',
                    'file-forbidden-type'                 => '文件类型或扩展名不允许。',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => '资产',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => '资产',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => '资产',
            'property'         => '属性',
            'comment'          => '评论',
            'linked_resources' => '关联资源',
            'directory'        => '目录',
            'tag'              => '标签',
            'create'           => '创建',
            'edit'             => '编辑',
            'update'           => '更新',
            'delete'           => '删除',
            'list'             => '列表',
            'view'             => '查看',
            'upload'           => '上传',
            're_upload'        => '重新上传',
            'mass_update'      => '批量更新',
            'mass_delete'      => '批量删除',
            'download'         => '下载',
            'custom_download'  => '自定义下载',
            'rename'           => '重命名',
            'move'             => '移动',
            'copy'             => '复制',
            'copy-structure'   => '复制目录结构',
            'download-zip'     => '下载 Zip',
            'asset-assign'     => '分配资产',
        ],

        'validation' => [
            'asset' => [
                'required' => '字段 :attribute 是必需的。',
            ],

            'comment' => [
                'required' => '评论内容是必需的。',
            ],
            'tag' => [
                'name' => [
                    'required' => '标签字段是必需的。',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => '名称字段是必需的。',
                    'unique'   => '名称已被使用。',
                ],
                'language' => [
                    'not-found' => '所选语言未找到或当前已被禁用。',
                ],
            ],
        ],

        'errors' => [
            '401' => '此操作未经授权。',
        ],
    ],
];
