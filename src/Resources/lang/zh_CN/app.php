<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => '数字资产管理', ,
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => '删除此目录将同时删除其下所有子目录。此操作是永久性的，无法撤销。',
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
                'title' => '数字资产管理',

                'datagrid' => [
                    'file-name'      => '文件名称',
                    'tags'           => '标签',
                    'property-name'  => '属性名称',
                    'property-value' => '属性值',
                    'created-at'     => '创建时间',
                    'updated-at'     => '更新时间',
                    'extension'      => '扩展名',
                    'path'           => '路径',
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
                        'download-zip'              => '下载 ZIP',
                        'paste'                     => '粘贴',
                        'add-directory'             => '添加目录',
                        'upload-files'              => '上传文件',
                        'copy-directory-structured' => '复制目录结构',
                    ],

                    'not-found'                                 => '未找到目录',
                    'created-success'                           => '目录创建成功',
                    'updated-success'                           => '目录更新成功',
                    'moved-success'                             => '目录移动成功',
                    'can-not-deleted'                           => '无法删除目录，因为它是根目录。',
                    'deleting-in-progress'                      => '目录删除中',
                    'can-not-copy'                              => '无法复制目录，因为它是根目录。',
                    'coping-in-progress'                        => '目录结构复制中。',
                    'asset-not-found'                           => '未找到资产',
                    'asset-renamed-success'                     => '资产重命名成功',
                    'asset-moved-success'                       => '资产移动成功',
                    'asset-name-already-exist'                  => '新名称已经存在，与另一个名为 :asset_name 的资产重复',
                    'asset-name-conflict-in-the-same-directory' => '资产名称与同一目录中的现有文件冲突。',
                    'old-file-not-found'                        => '未找到路径 :old_path 下的请求文件。',
                    'image-name-is-the-same'                    => '此名称已存在，请输入一个不同的名称。',
                    'not-writable'                              => '您无权在位置“:path”执行 :actionType 操作 :type。',
                    'empty-directory'                           => '此目录为空。',
                    'failed-download-directory'                 => '创建 ZIP 文件失败。',
                ],

                'title'       => '数字资产管理',
                'description' => '工具可以帮助您在一个地方组织、存储和管理所有媒体资产',
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
                    ],
                ],
                'comments' => [
                    'index'  => '添加评论',
                    'create' => [
                        'create-success' => '评论已成功添加',
                    ],
                    'post-comment' => '发布评论',
                    'post-reply'   => '发布回复',
                    'reply'        => '回复',
                    'add-reply'    => '添加回复',
                    'add-comment'  => '添加评论',
                    'no-comments'  => '尚无评论',
                ],

                'edit' => [
                    'title'              => '编辑资产',
                    'name'               => '名称',
                    'value'              => '值',
                    'back-btn'           => '返回',
                    'save-btn'           => '保存',
                    'embedded_meta_info' => '嵌入元信息',
                    'custom_meta_info'   => '自定义元信息',
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
                        'width'              => '宽度 (px)',
                        'width-placeholder'  => '200',
                        'height'             => '高度 (px)',
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
                    'failed-to-read'            => '读取图像元数据失败 :exception',
                    'file_re_upload_success'    => '文件重新上传成功。',
                ],

                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '产品',
                            'category'      => '分类',
                            'product-sku'   => '产品 SKU：',
                            'category code' => '分类代码：',
                            'resource-type' => '资源类型',
                            'resource'      => '资源',
                            'resource-view' => '资源视图',
                        ],
                    ],
                ],
                'delete-success'                          => '资产删除成功',
                'delete-failed-due-to-attached-resources' => '资产删除失败，因为它关联了资源 (资产名称：:assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => '批量删除成功。',
                    'files_upload_success' => '文件上传成功。',
                    'file_upload_success'  => '文件上传成功。',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => '資產',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => '資產',
                ],
            ],
        ],
        'acl' => [
            'menu'             => '壩',
            'asset'            => '資產',
            'property'         => '財產',
            'comment'          => '評論',
            'linked_resources' => '連結資源',
            'directory'        => '目錄',
            'tag'              => '標籤',
            'create'           => '創造',
            'edit'             => '編輯',
            'update'           => '更新',
            'delete'           => '刪除',
            'list'             => '清單',
            'view'             => '看法',
            'upload'           => '上傳',
            're_upload'        => '重新上傳',
            'mass_update'      => '大量更新',
            'mass_delete'      => '大量刪除',
            'download'         => '下載',
            'custom_download'  => '自訂下載',
            'rename'           => '重新命名',
            'move'             => '移動',
            'copy'             => '複製',
            'copy-structure'   => '複製目錄結構',
            'download-zip'     => '下載郵編',
            'asset-assign'     => '分配資產',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute 欄位是必需的。',
            ],

            'comment' => [
                'required' => '評論訊息是必需的。',
            ],

            'property' => [
                'name' => [
                    'required' => '名稱欄位是必需的。',
                    'unique'   => '該名稱已被佔用。',
                ],
            ],
        ],

        'errors' => [
            '401' => '此操作未經授權。',
        ],
    ],
];
