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
                    'message' => '이 디렉터리를 삭제하면 내부의 모든 하위 디렉터리도 함께 삭제됩니다. 이 작업은 영구적이며 취소할 수 없습니다.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => '자산 추가',
                    'assign-assets' => '자산 할당',
                    'assign'        => '할당',
                    'preview-asset' => '자산 미리보기',
                    'preview'       => '미리보기',
                    'remove'        => '제거',
                    'download'      => '다운로드',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => '파일 이름',
                    'tags'           => '태그',
                    'property-name'  => '속성 이름',
                    'property-value' => '속성 값',
                    'created-at'     => '생성 일시',
                    'updated-at'     => '수정 일시',
                    'extension'      => '확장자',
                    'path'           => '경로',
                    'size'           => '크기',
                ],

                'directory' => [
                    'title'        => '디렉터리',
                    'create'       => [
                        'title'    => '디렉터리 생성',
                        'name'     => '이름',
                        'save-btn' => '디렉터리 저장',
                    ],

                    'rename' => [
                        'title' => '디렉터리 이름 바꾸기',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => '자산 이름 바꾸기',
                            'save-btn' => '자산 저장',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => '삭제',
                        'rename'                    => '이름 바꾸기',
                        'copy'                      => '복사',
                        'download'                  => '다운로드',
                        'download-zip'              => 'Zip 다운로드',
                        'paste'                     => '붙여넣기',
                        'add-directory'             => '디렉터리 추가',
                        'upload-files'              => '파일 업로드',
                        'copy-directory-structured' => '디렉터리 구조 복사',
                        'get-by-id'                 => 'ID로 가져오기',
                        'comment'                   => '댓글',
                    ],

                    'linked-resources'                          => '연결된 리소스',
                    'not-found'                                 => '디렉터리를 찾을 수 없습니다',
                    'created-success'                           => '디렉터리가 성공적으로 생성되었습니다',
                    'updated-success'                           => '디렉터리가 성공적으로 업데이트되었습니다',
                    'moved-success'                             => '디렉터리가 성공적으로 이동되었습니다',
                    'fetch-all-success'                         => '디렉터리를 성공적으로 가져왔습니다',
                    'can-not-deleted'                           => '루트 디렉터리이므로 삭제할 수 없습니다.',
                    'deleting-in-progress'                      => '디렉터리 삭제가 진행 중입니다',
                    'can-not-copy'                              => '루트 디렉터리이므로 복사할 수 없습니다.',
                    'coping-in-progress'                        => '디렉터리 구조 복사가 진행 중입니다.',
                    'asset-not-found'                           => '자산을 찾을 수 없습니다',
                    'asset-renamed-success'                     => '자산 이름이 성공적으로 변경되었습니다',
                    'asset-moved-success'                       => '자산이 성공적으로 이동되었습니다',
                    'asset-name-already-exist'                  => '새 이름이 이미 :asset_name 이라는 다른 자산에 존재합니다',
                    'asset-name-conflict-in-the-same-directory' => '자산 이름이 같은 디렉터리의 기존 파일과 충돌합니다.',
                    'old-file-not-found'                        => ':old_path 경로에서 요청한 파일을 찾을 수 없습니다.',
                    'image-name-is-the-same'                    => '이 이름은 이미 존재합니다. 다른 이름을 입력해 주세요.',
                    'not-writable'                              => '이 위치 ":path" 에서 :type 을(를) :actionType 할 수 없습니다.',
                    'empty-directory'                           => '이 디렉터리는 비어 있습니다.',
                    'failed-download-directory'                 => 'zip 파일을 생성하지 못했습니다.',
                    'not-allowed'                               => '스크립트 파일 업로드는 허용되지 않습니다.',
                ],

                'title'       => 'DAM',
                'description' => '이 도구는 모든 미디어 자산을 한 곳에서 정리, 저장 및 관리하는 데 도움이 됩니다',
                'root'        => '루트',
                'upload'      => '업로드',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => '자산 속성',
                        'create-btn' => '속성 생성',

                        'datagrid'      => [
                            'name'     => '이름',
                            'type'     => '유형',
                            'language' => '언어',
                            'value'    => '값',
                            'edit'     => '편집',
                            'delete'   => '삭제',
                        ],

                        'create'     => [
                            'title'    => '속성 생성',
                            'name'     => '이름',
                            'type'     => '유형',
                            'language' => '언어',
                            'value'    => '값',
                            'save-btn' => '저장',
                        ],
                        'edit' => [
                            'title' => '속성 편집',
                        ],
                        'delete-success' => '자산 속성이 성공적으로 삭제되었습니다',
                        'create-success' => '자산 속성이 성공적으로 생성되었습니다',
                        'update-success' => '자산 속성이 성공적으로 업데이트되었습니다',
                        'not-found'      => '속성을 찾을 수 없습니다',
                        'found-success'  => '속성을 성공적으로 찾았습니다',
                    ],
                ],
                'comments' => [
                    'index'  => '댓글 추가',
                    'create' => [
                        'create-success' => '댓글이 성공적으로 추가되었습니다',
                        'create-failure' => '댓글 생성에 실패했습니다',
                    ],
                    'post-comment'    => '댓글 게시',
                    'post-reply'      => '답글 게시',
                    'reply'           => '답글',
                    'add-reply'       => '답글 추가',
                    'add-comment'     => '댓글 추가',
                    'no-comments'     => '아직 댓글이 없습니다',
                    'not-found'       => '댓글을 찾을 수 없습니다',
                    'updated-success' => '댓글이 성공적으로 업데이트되었습니다',
                    'update-failed'   => '댓글 업데이트에 실패했습니다',
                    'delete-success'  => '자산 댓글이 성공적으로 삭제되었습니다',
                    'delete-failed'   => '자산 댓글 삭제에 실패했습니다',
                ],
                'edit' => [
                    'title'              => '자산 편집',
                    'name'               => '이름',
                    'value'              => '값',
                    'back-btn'           => '뒤로',
                    'save-btn'           => '저장',
                    'embedded_meta_info' => '임베디드 메타 정보',
                    'custom_meta_info'   => '사용자 정의 메타 정보',
                    'tags'               => '태그',
                    'select-tags'        => '태그를 선택하거나 생성하세요',
                    'tag'                => '태그',
                    'directory-path'     => '디렉터리 경로',
                    'add_tags'           => '태그 추가',
                    'tab'                => [
                        'preview'          => '미리보기',
                        'properties'       => '속성',
                        'comments'         => '댓글',
                        'linked_resources' => '연결된 리소스',
                        'history'          => '기록',
                    ],
                    'button' => [
                        'download'        => '다운로드',
                        'custom_download' => '사용자 정의 다운로드',
                        'rename'          => '이름 바꾸기',
                        're_upload'       => '재업로드',
                        'delete'          => '삭제',
                    ],

                    'custom-download' => [
                        'title'              => '사용자 정의 다운로드',
                        'format'             => '형식',
                        'width'              => '너비 (px)',
                        'width-placeholder'  => '200',
                        'height'             => '높이 (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => '다운로드',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => '원본',
                        ],
                    ],

                    'tag-already-exists'        => '태그가 이미 존재합니다',
                    'image-source-not-readable' => '이미지 소스를 읽을 수 없습니다',
                    'failed-to-read'            => '이미지 메타데이터를 읽지 못했습니다 :exception',
                    'file-re-upload-success'    => '파일이 성공적으로 재업로드되었습니다.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => '상품',
                            'category'      => '카테고리',
                            'product-sku'   => '상품 Sku: ',
                            'category code' => '카테고리 코드: ',
                            'resource-type' => '리소스 유형',
                            'resource'      => '리소스',
                            'resource-view' => '리소스 보기',
                        ],
                    ],
                    'found-success' => '리소스를 성공적으로 찾았습니다',
                    'not-found'     => '리소스를 찾을 수 없습니다',
                ],
                'tags' => [
                    'index'  => '태그 추가',
                    'create' => [
                        'create-success' => '태그가 성공적으로 추가되었습니다',
                        'create-failure' => '태그 생성에 실패했습니다',
                    ],

                    'no-comments'    => '아직 태그가 없습니다',
                    'found-success'  => '태그를 성공적으로 찾았습니다',
                    'not-found'      => '태그를 찾을 수 없습니다',
                    'update-success' => '태그가 성공적으로 업데이트되었습니다',
                    'update-failed'  => '태그 업데이트에 실패했습니다',
                    'delete-success' => '자산 태그가 성공적으로 제거되었습니다',
                    'delete-failed'  => '자산 태그 삭제에 실패했습니다',
                ],
                'delete-success'                          => '자산이 성공적으로 삭제되었습니다',
                'delete-failed-due-to-attached-resources' => '자산이 사용 중입니다. 삭제 전에 연결을 해제하세요',
                'datagrid'                                => [
                    'mass-delete-success'                 => '대량 삭제가 성공적으로 완료되었습니다.',
                    'files-upload-success'                => '파일이 성공적으로 업로드되었습니다.',
                    'file-upload-success'                 => '파일이 성공적으로 업로드되었습니다.',
                    'not-found'                           => '파일을 찾을 수 없습니다',
                    'edit-success'                        => '파일이 성공적으로 업로드되었습니다',
                    'show-success'                        => '파일을 성공적으로 찾았습니다',
                    'update-success'                      => '파일이 성공적으로 업데이트되었습니다',
                    'not-found-to-update'                 => '파일이 존재하지 않습니다',
                    'not-found-to-destroy'                => '파일이 존재하지 않습니다',
                    'files-upload-failed'                 => '파일 업로드에 실패했습니다.',
                    'file-upload-failed'                  => '파일 업로드에 실패했습니다',
                    'invalid-file'                        => '잘못된 파일이 제공되었습니다',
                    'invalid-file-format'                 => '잘못된 형식',
                    'invalid-file-format-or-not-provided' => '제공된 파일이 없거나 형식이 잘못되었습니다.',
                    'download-image-failed'               => 'URL에서 이미지를 다운로드하지 못했습니다',
                    'file-process-failed'                 => '일부 파일 처리에 실패했습니다',
                    'file-forbidden-type'                 => '파일 유형 또는 확장자가 금지되어 있습니다.',
                    'file-too-large'                      => '파일이 너무 큽니다. 허용되는 최대 크기는 :size 입니다.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => '자산',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => '자산',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => '자산',
            'property'         => '속성',
            'comment'          => '댓글',
            'linked_resources' => '연결된 리소스',
            'directory'        => '디렉터리',
            'tag'              => '태그',
            'create'           => '생성',
            'edit'             => '편집',
            'update'           => '업데이트',
            'delete'           => '삭제',
            'list'             => '목록',
            'view'             => '보기',
            'upload'           => '업로드',
            're_upload'        => '재업로드',
            'mass_update'      => '대량 업데이트',
            'mass_delete'      => '대량 삭제',
            'download'         => '다운로드',
            'custom_download'  => '사용자 정의 다운로드',
            'rename'           => '이름 바꾸기',
            'move'             => '이동',
            'copy'             => '복사',
            'copy-structure'   => '디렉터리 구조 복사',
            'download-zip'     => 'Zip 다운로드',
            'asset-assign'     => '자산 할당',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute 필드는 필수입니다.',
            ],

            'comment' => [
                'required' => '댓글 메시지는 필수입니다.',
            ],
            'tag' => [
                'name' => [
                    'required' => '태그 필드는 필수입니다.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => '이름 필드는 필수입니다.',
                    'unique'   => '이름이 이미 사용 중입니다.',
                ],
                'language' => [
                    'not-found' => '선택한 언어를 찾을 수 없거나 현재 비활성화되어 있습니다.',
                ],
            ],
        ],

        'errors' => [
            '401' => '이 작업은 권한이 없습니다.',
        ],
    ],
];
