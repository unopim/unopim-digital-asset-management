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
                    'message' => 'このディレクトリを削除すると、その中にあるすべてのサブディレクトリも削除されます。この操作は永久的で元に戻すことはできません。',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'アセットを追加',
                    'assign-assets' => 'アセットを割り当て',
                    'assign'        => '割り当て',
                    'preview-asset' => 'アセットをプレビュー',
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
                    'created-at'     => '作成日',
                    'updated-at'     => '更新日',
                    'extension'      => '拡張子',
                    'path'           => 'パス',
                    'size'           => 'サイズ',
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
                            'title'    => 'アセット名を変更',
                            'save-btn' => 'アセットを保存',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => '削除',
                        'rename'                    => '名前変更',
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
                    'fetch-all-success'                         => 'ディレクトリが正常に取得されました',
                    'can-not-deleted'                           => 'ルートディレクトリのため、このディレクトリは削除できません。',
                    'deleting-in-progress'                      => 'ディレクトリ削除中',
                    'can-not-copy'                              => 'ルートディレクトリのため、このディレクトリはコピーできません。',
                    'coping-in-progress'                        => 'ディレクトリ構造のコピー中',
                    'asset-not-found'                           => 'アセットが見つかりません',
                    'asset-renamed-success'                     => 'アセットが正常に名前変更されました',
                    'asset-moved-success'                       => 'アセットが正常に移動されました',
                    'asset-name-already-exist'                  => '新しい名前は別のアセット ":asset_name" と既に存在します',
                    'asset-name-conflict-in-the-same-directory' => 'アセット名が同じディレクトリ内の既存のファイルと競合しています。',
                    'old-file-not-found'                        => 'パス ":old_path" に要求されたファイルが見つかりません。',
                    'image-name-is-the-same'                    => 'この名前は既に存在します。別の名前を入力してください。',
                    'not-writable'                              => 'この場所 ":path" では :actionType 操作が許可されていません。',
                    'empty-directory'                           => 'このディレクトリは空です。',
                    'failed-download-directory'                 => 'ZIPファイルの作成に失敗しました。',
                    'not-allowed'                               => 'スクリプトファイルのアップロードは許可されていません。',
                ],

                'title'       => 'DAM',
                'description' => 'このツールは、すべてのメディアアセットを一箇所で整理、保存、管理するのに役立ちます。',
                'root'        => 'ルート',
                'upload'      => 'アップロード',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'アセットプロパティ',
                        'create-btn' => 'プロパティ作成',

                        'datagrid'      => [
                            'name'     => '名前',
                            'type'     => 'タイプ',
                            'language' => '言語',
                            'value'    => '値',
                            'edit'     => '編集',
                            'delete'   => '削除',
                        ],

                        'create'     => [
                            'title'    => 'プロパティ作成',
                            'name'     => '名前',
                            'type'     => 'タイプ',
                            'language' => '言語',
                            'value'    => '値',
                            'save-btn' => '保存',
                        ],
                        'edit' => [
                            'title' => 'プロパティ編集',
                        ],
                        'delete-success' => 'アセットプロパティが正常に削除されました',
                        'create-success' => 'アセットプロパティが正常に作成されました',
                        'update-success' => 'アセットプロパティが正常に更新されました',
                        'not-found'      => 'プロパティが見つかりません',
                        'found-success'  => 'プロパティが正常に見つかりました',

                    ],
                ],
                'comments' => [
                    'index'  => 'コメントを追加',
                    'create' => [
                        'create-success' => 'コメントが正常に追加されました',
                        'create-failure' => 'コメントの作成に失敗しました',
                    ],
                    'post-comment'    => 'コメントを投稿',
                    'post-reply'      => '返信を投稿',
                    'reply'           => '返信',
                    'add-reply'       => '返信を追加',
                    'add-comment'     => 'コメントを追加',
                    'no-comments'     => 'まだコメントはありません',
                    'not-found'       => 'コメントが見つかりません',
                    'updated-success' => 'コメントが正常に更新されました',
                    'update-failed'   => 'コメントの更新に失敗しました',
                    'delete-success'  => 'アセットコメントが正常に削除されました',
                    'delete-failed'   => 'アセットコメントの削除に失敗しました',
                ],

                'edit' => [
                    'title'              => 'アセット編集',
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
                        'rename'          => '名前変更',
                        're_upload'       => '再アップロード',
                        'delete'          => '削除',
                    ],

                    'custom-download' => [
                        'title'              => 'カスタムダウンロード',
                        'format'             => 'フォーマット',
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

                    'tag-already-exists'        => 'タグは既に存在します',
                    'image-source-not-readable' => '画像ソースは読み取れません',
                    'failed-to-read'            => '画像のメタデータの読み取りに失敗しました :exception',
                    'file-re-upload-success'    => 'ファイルが再アップロードされました。',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '製品',
                            'category'      => 'カテゴリ',
                            'product-sku'   => '製品SKU: ',
                            'category code' => 'カテゴリコード: ',
                            'resource-type' => 'リソースタイプ',
                            'resource'      => 'リソース',
                            'resource-view' => 'リソースビュー',
                        ],
                    ],
                    'found-success' => 'リソースが正常に見つかりました',
                    'not-found'     => 'リソースが見つかりません',
                ],
                'tags' => [
                    'index'  => 'タグを追加',
                    'create' => [
                        'create-success' => 'タグが正常に追加されました',
                        'create-failure' => 'タグの作成に失敗しました',
                    ],

                    'no-comments'    => 'タグはまだありません',
                    'found-success'  => 'タグが正常に見つかりました',
                    'not-found'      => 'タグが見つかりません',
                    'update-success' => 'タグが正常に更新されました',
                    'update-failed'  => 'タグの更新に失敗しました',
                    'delete-success' => 'アセットのタグが正常に削除されました',
                    'delete-failed'  => 'アセットタグの削除に失敗しました',
                ],
                'delete-success'                          => 'アセットが正常に削除されました',
                'delete-failed-due-to-attached-resources' => 'アセットは使用中です。削除する前にリンクを解除してください',
                'datagrid'                                => [
                    'mass-delete-success'                 => '一括削除が正常に完了しました。',
                    'files-upload-success'                => 'ファイルが正常にアップロードされました。',
                    'file-upload-success'                 => 'ファイルが正常にアップロードされました。',
                    'not-found'                           => 'ファイルが見つかりません',
                    'edit-success'                        => 'ファイルが正常にアップロードされました',
                    'show-success'                        => 'ファイルが正常に見つかりました',
                    'update-success'                      => 'ファイルが正常に更新されました',
                    'not-found-to-update'                 => 'ファイルは存在しません',
                    'not-found-to-destroy'                => 'ファイルは存在しません',
                    'files-upload-failed'                 => 'ファイルのアップロードに失敗しました。',
                    'file-upload-failed'                  => 'ファイルのアップロードに失敗しました',
                    'invalid-file'                        => '無効なファイルが提供されました',
                    'invalid-file-format'                 => '無効なフォーマット',
                    'invalid-file-format-or-not-provided' => 'ファイルが提供されていないか、無効なフォーマットです。',
                    'download-image-failed'               => '画像のダウンロードに失敗しました',
                    'file-process-failed'                 => '一部のファイルの処理に失敗しました',
                    'file-forbidden-type'                 => 'ファイルのタイプまたは拡張子が禁止されています。',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'アセット',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'アセット',
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
            'list'             => '一覧',
            'view'             => '表示',
            'upload'           => 'アップロード',
            're_upload'        => '再アップロード',
            'mass_update'      => '一括更新',
            'mass_delete'      => '一括削除',
            'download'         => 'ダウンロード',
            'custom_download'  => 'カスタムダウンロード',
            'rename'           => '名前変更',
            'move'             => '移動',
            'copy'             => 'コピー',
            'copy-structure'   => 'ディレクトリ構造をコピー',
            'download-zip'     => 'ZIPをダウンロード',
            'asset-assign'     => 'アセットを割り当て',
        ],

        'validation' => [
            'asset' => [
                'required' => 'フィールド :attribute は必須です。',
            ],

            'comment' => [
                'required' => 'コメントメッセージは必須です。',
            ],
            'tag' => [
                'name' => [
                    'required' => 'タグフィールドは必須です。',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => '名前フィールドは必須です。',
                    'unique'   => 'この名前は既に使用されています。',
                ],
                'language' => [
                    'not-found' => '選択された言語が見つからないか、現在無効になっています。',
                ],
            ],
        ],

        'errors' => [
            '401' => 'この操作は認証されていません。',
        ],
    ],
];
