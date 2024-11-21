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
                    'message' => 'このディレクトリを削除すると、内部のすべてのサブディレクトリも削除されます。この操作は取り消せません。',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'メディアを追加',
                    'assign-assets' => 'メディアを割り当て',
                    'assign'        => '割り当て',
                    'preview-asset' => 'メディアのプレビュー',
                    'preview'       => 'プレビュー',
                    'remove'        => '削除',
                    'download'      => 'ダウンロード',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'ファイル名',
                    'tags'           => 'タグ',
                    'property-name'  => 'プロパティ名',
                    'property-value' => 'プロパティ値',
                    'created-at'     => '作成日時',
                    'updated-at'     => '更新日時',
                    'extension'      => '拡張子',
                    'path'           => 'パス',
                ],

                'directory' => [
                    'title'        => 'ディレクトリ',
                    'create'       => [
                        'title'    => 'ディレクトリを作成',
                        'name'     => '名前',
                        'save-btn' => 'ディレクトリを保存',
                    ],

                    'rename' => [
                        'title' => 'ディレクトリ名を変更',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'メディア名を変更',
                            'save-btn' => 'メディアを保存',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => '削除',
                        'rename'                    => '名前を変更',
                        'copy'                      => 'コピー',
                        'download'                  => 'ダウンロード',
                        'download-zip'              => 'ZIPをダウンロード',
                        'paste'                     => '貼り付け',
                        'add-directory'             => 'ディレクトリを追加',
                        'upload-files'              => 'ファイルをアップロード',
                        'copy-directory-structured' => 'ディレクトリ構造をコピー',
                    ],

                    'not-found'                                 => 'ディレクトリが見つかりません',
                    'created-success'                           => 'ディレクトリが正常に作成されました',
                    'updated-success'                           => 'ディレクトリが正常に更新されました',
                    'moved-success'                             => 'ディレクトリが正常に移動されました',
                    'can-not-deleted'                           => 'ルートディレクトリのため削除できません。',
                    'deleting-in-progress'                      => 'ディレクトリの削除が進行中です',
                    'can-not-copy'                              => 'ルートディレクトリのためコピーできません。',
                    'coping-in-progress'                        => 'ディレクトリ構造のコピーが進行中です。',
                    'asset-not-found'                           => 'メディアが見つかりません',
                    'asset-renamed-success'                     => 'メディアの名前が正常に変更されました',
                    'asset-moved-success'                       => 'メディアが正常に移動されました',
                    'asset-name-already-exist'                  => '新しい名前は別のメディア :asset_name としてすでに存在します',
                    'asset-name-conflict-in-the-same-directory' => 'メディア名が同じディレクトリ内の既存ファイルと競合しています。',
                    'old-file-not-found'                        => 'パス :old_path にあるファイルが見つかりませんでした。',
                    'image-name-is-the-same'                    => 'この名前はすでに存在します。別の名前を入力してください。',
                    'not-writable'                              => 'この場所 ":path" で :type を :actionType する権限がありません。',
                    'empty-directory'                           => 'このディレクトリは空です。',
                    'failed-download-directory'                 => 'ZIPファイルの作成に失敗しました。',
                ],

                'title'       => 'DAM',
                'description' => 'このツールを使用すると、すべてのメディアを一か所で整理、保存、および管理できます',
                'root'        => 'ルート',
                'upload'      => 'アップロード',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'メディアプロパティ',
                        'create-btn' => 'プロパティを作成',

                        'datagrid'      => [
                            'name'     => '名前',
                            'type'     => 'タイプ',
                            'language' => '言語',
                            'value'    => '値',
                            'edit'     => '編集',
                            'delete'   => '削除',
                        ],

                        'create'     => [
                            'title'    => 'プロパティを作成',
                            'name'     => '名前',
                            'type'     => 'タイプ',
                            'language' => '言語',
                            'value'    => '値',
                            'save-btn' => '保存',
                        ],
                        'edit' => [
                            'title' => 'プロパティを編集',
                        ],
                        'delete-success' => 'メディアプロパティが正常に削除されました',
                        'create-success' => 'メディアプロパティが正常に作成されました',
                        'update-success' => 'メディアプロパティが正常に更新されました',
                    ],
                ],
                'comments' => [
                    'index'  => 'コメントを追加',
                    'create' => [
                        'create-success' => 'コメントが正常に追加されました',
                    ],
                    'post-comment' => 'コメントを投稿',
                    'post-reply'   => '返信を投稿',
                    'reply'        => '返信',
                    'add-reply'    => '返信を追加',
                    'add-comment'  => 'コメントを追加',
                    'no-comments'  => 'コメントがまだありません',

                ],
                'edit' => [
                    'title'              => 'メディアを編集',
                    'name'               => '名前',
                    'value'              => '値',
                    'back-btn'           => '戻る',
                    'save-btn'           => '保存',
                    'embedded_meta_info' => '埋め込みメタ情報',
                    'custom_meta_info'   => 'カスタムメタ情報',
                    'tags'               => 'タグ',
                    'select-tags'        => 'タグを選択または作成',
                    'tag'                => 'タグ',
                    'directory-path'     => 'ディレクトリパス',
                    'add_tags'           => 'タグを追加',
                    'tab'                => [
                        'preview'          => 'プレビュー',
                        'properties'       => 'プロパティ',
                        'comments'         => 'コメント',
                        'linked_resources' => 'リンクされたリソース',
                        'history'          => '履歴',
                    ],
                    'button' => [
                        'download'        => 'ダウンロード',
                        'custom_download' => 'カスタムダウンロード',
                        'rename'          => '名前を変更',
                        're_upload'       => '再アップロード',
                        'delete'          => '削除',
                    ],

                    'custom-download' => [
                        'title'              => 'カスタムダウンロード',
                        'format'             => '形式',
                        'width'              => '幅 (px)',
                        'width-placeholder'  => '200',
                        'height'             => '高さ (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'ダウンロード',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'オリジナル',
                        ],
                    ],

                    'tag-already-exists'        => 'タグはすでに存在します',
                    'image-source-not-readable' => '画像ソースを読み取れません',
                    'failed-to-read'            => '画像メタデータの読み取りに失敗しました :exception',
                    'file_re_upload_success'    => 'ファイルが正常に再アップロードされました。',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '製品',
                            'category'      => 'カテゴリ',
                            'product-sku'   => '製品 SKU: ',
                            'category code' => 'カテゴリ コード: ',
                            'resource-type' => 'リソース タイプ',
                            'resource'      => 'リソース',
                            'resource-view' => 'リソース ビュー',
                        ],
                    ],
                ],
                'delete-success'                          => 'アセットが正常に削除されました',
                'delete-failed-due-to-attached-resources' => 'アセットはリソースにリンクされているため削除できませんでした (アセット名: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => '一括削除に成功しました。',
                    'files_upload_success' => 'ファイルが正常にアップロードされました。',
                    'file_upload_success'  => 'ファイルが正常にアップロードされました。',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => '資産',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => '資産',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'アセット',
            'property'         => 'プロパティ',
            'comment'          => 'コメント',
            'linked_resources' => 'リンクされたリソース',
            'directory'        => 'ディレクトリ',
            'tag'              => 'タグ',
            'create'           => '作成',
            'edit'             => '編集',
            'update'           => '更新',
            'delete'           => '削除',
            'list'             => 'リスト',
            'view'             => '表示',
            'upload'           => 'アップロード',
            're_upload'        => '再アップロード',
            'mass_update'      => '一括更新',
            'mass_delete'      => '一括削除',
            'download'         => 'ダウンロード',
            'custom_download'  => 'カスタムダウンロード',
            'rename'           => '名前の変更',
            'move'             => '移動',
            'copy'             => 'コピー',
            'copy-structure'   => 'ディレクトリ構造のコピー',
            'download-zip'     => 'ZIP のダウンロード',
            'asset-assign'     => 'アセットの割り当て',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute フィールドは必須です。',
            ],

            'comment' => [
                'required' => 'コメントメッセージは必須です。',
            ],

            'property' => [
                'name' => [
                    'required' => '名前フィールドは必須です。',
                    'unique'   => '名前はすでに使用されています。',
                ],
            ],
        ],

        'errors' => [
            '401' => 'このアクションは許可されていません。',
        ],
    ],
];
