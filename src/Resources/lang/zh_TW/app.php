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
                    'message' => '刪除此目錄將同時刪除其中的所有子目錄。此操作是永久性的，無法復原。',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => '新增資產',
                    'assign-assets' => '指派資產',
                    'assign'        => '指派',
                    'preview-asset' => '預覽資產',
                    'preview'       => '預覽',
                    'remove'        => '移除',
                    'download'      => '下載',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => '檔案名稱',
                    'tags'           => '標籤',
                    'property-name'  => '屬性名稱',
                    'property-value' => '屬性值',
                    'created-at'     => '建立時間',
                    'updated-at'     => '更新時間',
                    'extension'      => '副檔名',
                    'path'           => '路徑',
                    'size'           => '大小',
                ],

                'directory' => [
                    'title'        => '目錄',
                    'create'       => [
                        'title'    => '建立目錄',
                        'name'     => '名稱',
                        'save-btn' => '儲存目錄',
                    ],

                    'rename' => [
                        'title' => '重新命名目錄',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => '重新命名資產',
                            'save-btn' => '儲存資產',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => '刪除',
                        'rename'                    => '重新命名',
                        'copy'                      => '複製',
                        'download'                  => '下載',
                        'download-zip'              => '下載 Zip',
                        'paste'                     => '貼上',
                        'add-directory'             => '新增目錄',
                        'upload-files'              => '上傳檔案',
                        'copy-directory-structured' => '複製目錄結構',
                        'get-by-id'                 => '依 ID 取得',
                        'comment'                   => '留言',
                    ],

                    'linked-resources'                          => '連結的資源',
                    'not-found'                                 => '找不到目錄',
                    'created-success'                           => '目錄建立成功',
                    'updated-success'                           => '目錄更新成功',
                    'moved-success'                             => '目錄移動成功',
                    'fetch-all-success'                         => '目錄擷取成功',
                    'can-not-deleted'                           => '此為根目錄，無法刪除。',
                    'deleting-in-progress'                      => '目錄刪除進行中',
                    'can-not-copy'                              => '此為根目錄，無法複製。',
                    'coping-in-progress'                        => '目錄結構複製進行中。',
                    'asset-not-found'                           => '找不到資產',
                    'asset-renamed-success'                     => '資產重新命名成功',
                    'asset-moved-success'                       => '資產移動成功',
                    'asset-name-already-exist'                  => '此新名稱已存在於另一個名為 :asset_name 的資產',
                    'asset-name-conflict-in-the-same-directory' => '資產名稱與同一目錄中現有檔案發生衝突。',
                    'old-file-not-found'                        => '在路徑 :old_path 找不到所請求的檔案。',
                    'image-name-is-the-same'                    => '此名稱已存在，請輸入另一個名稱。',
                    'not-writable'                              => '您無法在此位置 ":path" :actionType 一個 :type。',
                    'empty-directory'                           => '此目錄為空。',
                    'failed-download-directory'                 => '建立 zip 檔案失敗。',
                    'not-allowed'                               => '不允許上傳指令碼檔案。',
                ],

                'title'       => 'DAM',
                'description' => '此工具可協助您在一個地方組織、儲存及管理所有媒體資產',
                'root'        => '根目錄',
                'upload'      => '上傳',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => '資產屬性',
                        'create-btn' => '建立屬性',

                        'datagrid'      => [
                            'name'     => '名稱',
                            'type'     => '類型',
                            'language' => '語言',
                            'value'    => '值',
                            'edit'     => '編輯',
                            'delete'   => '刪除',
                        ],

                        'create'     => [
                            'title'    => '建立屬性',
                            'name'     => '名稱',
                            'type'     => '類型',
                            'language' => '語言',
                            'value'    => '值',
                            'save-btn' => '儲存',
                        ],
                        'edit' => [
                            'title' => '編輯屬性',
                        ],
                        'delete-success' => '資產屬性刪除成功',
                        'create-success' => '資產屬性建立成功',
                        'update-success' => '資產屬性更新成功',
                        'not-found'      => '找不到屬性',
                        'found-success'  => '屬性尋找成功',
                    ],
                ],
                'comments' => [
                    'index'  => '新增留言',
                    'create' => [
                        'create-success' => '留言已成功新增',
                        'create-failure' => '留言建立失敗',
                    ],
                    'post-comment'    => '發佈留言',
                    'post-reply'      => '發佈回覆',
                    'reply'           => '回覆',
                    'add-reply'       => '新增回覆',
                    'add-comment'     => '新增留言',
                    'no-comments'     => '尚無留言',
                    'not-found'       => '找不到留言',
                    'updated-success' => '留言更新成功',
                    'update-failed'   => '留言更新失敗',
                    'delete-success'  => '資產留言刪除成功',
                    'delete-failed'   => '資產留言刪除失敗',
                ],
                'edit' => [
                    'title'              => '編輯資產',
                    'name'               => '名稱',
                    'value'              => '值',
                    'back-btn'           => '返回',
                    'save-btn'           => '儲存',
                    'embedded_meta_info' => '內嵌中繼資料',
                    'custom_meta_info'   => '自訂中繼資料',
                    'tags'               => '標籤',
                    'select-tags'        => '選擇或建立標籤',
                    'tag'                => '標籤',
                    'directory-path'     => '目錄路徑',
                    'add_tags'           => '新增標籤',
                    'tab'                => [
                        'preview'          => '預覽',
                        'properties'       => '屬性',
                        'comments'         => '留言',
                        'linked_resources' => '連結的資源',
                        'history'          => '歷程記錄',
                    ],
                    'button' => [
                        'download'        => '下載',
                        'custom_download' => '自訂下載',
                        'rename'          => '重新命名',
                        're_upload'       => '重新上傳',
                        'delete'          => '刪除',
                    ],

                    'custom-download' => [
                        'title'              => '自訂下載',
                        'format'             => '格式',
                        'width'              => '寬度 (px)',
                        'width-placeholder'  => '200',
                        'height'             => '高度 (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => '下載',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => '原始',
                        ],
                    ],

                    'tag-already-exists'        => '標籤已存在',
                    'image-source-not-readable' => '無法讀取影像來源',
                    'failed-to-read'            => '讀取影像中繼資料失敗 :exception',
                    'file-re-upload-success'    => '檔案重新上傳成功。',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '產品',
                            'category'      => '類別',
                            'product-sku'   => '產品 Sku：',
                            'category code' => '類別代碼：',
                            'resource-type' => '資源類型',
                            'resource'      => '資源',
                            'resource-view' => '資源檢視',
                        ],
                    ],
                    'found-success' => '資源尋找成功',
                    'not-found'     => '找不到資源',
                ],
                'tags' => [
                    'index'  => '新增標籤',
                    'create' => [
                        'create-success' => '標籤已成功新增',
                        'create-failure' => '標籤建立失敗',
                    ],

                    'no-comments'    => '尚無標籤',
                    'found-success'  => '標籤尋找成功',
                    'not-found'      => '找不到標籤',
                    'update-success' => '標籤更新成功',
                    'update-failed'  => '標籤更新失敗',
                    'delete-success' => '資產標籤移除成功',
                    'delete-failed'  => '資產標籤刪除失敗',
                ],
                'delete-success'                          => '資產刪除成功',
                'delete-failed-due-to-attached-resources' => '資產使用中，請先取消連結再刪除',
                'datagrid'                                => [
                    'mass-delete-success'                 => '大量刪除成功。',
                    'files-upload-success'                => '檔案上傳成功。',
                    'file-upload-success'                 => '檔案上傳成功。',
                    'not-found'                           => '找不到檔案',
                    'edit-success'                        => '檔案上傳成功',
                    'show-success'                        => '檔案尋找成功',
                    'update-success'                      => '檔案更新成功',
                    'not-found-to-update'                 => '檔案不存在',
                    'not-found-to-destroy'                => '檔案不存在',
                    'files-upload-failed'                 => '檔案上傳失敗。',
                    'file-upload-failed'                  => '檔案上傳失敗',
                    'invalid-file'                        => '提供的檔案無效',
                    'invalid-file-format'                 => '格式無效',
                    'invalid-file-format-or-not-provided' => '未提供檔案或格式無效。',
                    'download-image-failed'               => '從 URL 下載影像失敗',
                    'file-process-failed'                 => '部分檔案處理失敗',
                    'file-forbidden-type'                 => '檔案類型或副檔名被禁止。',
                    'file-too-large'                      => '檔案過大，允許的最大大小為 :size。',
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
            'menu'             => 'DAM',
            'asset'            => '資產',
            'property'         => '屬性',
            'comment'          => '留言',
            'linked_resources' => '連結的資源',
            'directory'        => '目錄',
            'tag'              => '標籤',
            'create'           => '建立',
            'edit'             => '編輯',
            'update'           => '更新',
            'delete'           => '刪除',
            'list'             => '列表',
            'view'             => '檢視',
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
            'download-zip'     => '下載 Zip',
            'asset-assign'     => '指派資產',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute 欄位為必填。',
            ],

            'comment' => [
                'required' => '留言內容為必填。',
            ],
            'tag' => [
                'name' => [
                    'required' => '標籤欄位為必填。',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => '名稱欄位為必填。',
                    'unique'   => '此名稱已被使用。',
                ],
                'language' => [
                    'not-found' => '找不到所選語言，或該語言目前已停用。',
                ],
            ],
        ],

        'errors' => [
            '401' => '此操作未經授權。',
        ],
    ],
];
