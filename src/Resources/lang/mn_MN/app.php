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
                    'message' => 'Энэ хавтсыг устгаснаар дотор байгаа бүх дэд хавтас мөн устгагдана. Энэ үйлдлийг буцаах боломжгүй.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Медиа нэмэх',
                    'assign-assets' => 'Медиа холбох',
                    'assign'        => 'Холбох',
                    'preview-asset' => 'Медиа урьдчилан харах',
                    'preview'       => 'Урьдчилан харах',
                    'remove'        => 'Устгах',
                    'download'      => 'Татаж авах',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'МХМ',

                'datagrid' => [
                    'file-name'      => 'Файлын нэр',
                    'tags'           => 'Шошго',
                    'property-name'  => 'Өмчлөлийн нэр',
                    'property-value' => 'Өмчлөлийн утга',
                    'created-at'     => 'Үүсгэсэн огноо',
                    'updated-at'     => 'Шинэчилсэн огноо',
                    'extension'      => 'Өргөтгөл',
                    'path'           => 'Зам',
                ],

                'directory' => [
                    'title'        => 'Хавтас',
                    'create'       => [
                        'title'    => 'Хавтас үүсгэх',
                        'name'     => 'Нэр',
                        'save-btn' => 'Хавтас хадгалах',
                    ],

                    'rename' => [
                        'title' => 'Хавтасны нэрийг өөрчлөх',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Медиа нэрийг өөрчлөх',
                            'save-btn' => 'Медиа хадгалах',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Устгах',
                        'rename'                    => 'Нэр солих',
                        'copy'                      => 'Хуулах',
                        'download'                  => 'Татаж авах',
                        'download-zip'              => 'ZIP татах',
                        'paste'                     => 'Буулгах',
                        'add-directory'             => 'Хавтас нэмэх',
                        'upload-files'              => 'Файл оруулах',
                        'copy-directory-structured' => 'Хавтасны бүтцийг хуулах',
                    ],

                    'not-found'                                 => 'Хавтас олдсонгүй',
                    'created-success'                           => 'Хавтас амжилттай үүсгэгдлээ',
                    'updated-success'                           => 'Хавтас амжилттай шинэчлэгдлээ',
                    'moved-success'                             => 'Хавтас амжилттай шилжүүлэгдлээ',
                    'can-not-deleted'                           => 'Энэ нь үндсэн хавтас тул устгах боломжгүй.',
                    'deleting-in-progress'                      => 'Хавтсыг устгаж байна...',
                    'can-not-copy'                              => 'Энэ нь үндсэн хавтас тул хуулах боломжгүй.',
                    'coping-in-progress'                        => 'Хавтасны бүтцийг хуулах үйлдэл явагдаж байна.',
                    'asset-not-found'                           => 'Медиа олдсонгүй',
                    'asset-renamed-success'                     => 'Медиа нэр амжилттай солигдлоо',
                    'asset-moved-success'                       => 'Медиа амжилттай шилжүүлэгдлээ',
                    'asset-name-already-exist'                  => 'Шинэ нэр нь өөр медиа :asset_name гэх нэртэй давхцаж байна',
                    'asset-name-conflict-in-the-same-directory' => 'Медиа нэр тухайн хавтас доторх өөр файлтай давхцаж байна.',
                    'old-file-not-found'                        => 'Зам :old_path доторх файл олдсонгүй.',
                    'image-name-is-the-same'                    => 'Энэ нэр аль хэдийн оршин байна. Өөр нэр оруулна уу.',
                    'not-writable'                              => 'Энэ байрлалд ":path" дээр :type :actionType үйлдэл хийх эрхгүй байна.',
                    'empty-directory'                           => 'Энэ хавтас хоосон байна.',
                    'failed-download-directory'                 => 'ZIP файл үүсгэхэд алдаа гарлаа.',
                ],

                'title'       => 'МХМ',
                'description' => 'Энэ хэрэгсэл нь таны бүх медиа файлыг нэг дор цэгцлэх, хадгалах, удирдах боломжийг олгоно.',
                'root'        => 'Үндсэн хавтас',
                'upload'      => 'Файл оруулах',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Asset Properties',
                        'create-btn' => 'Үл хөдлөх хөрөнгө үүсгэх',

                        'datagrid'      => [
                            'name'     => 'Нэр',
                            'type'     => 'Төрөл',
                            'language' => 'Хэл',
                            'value'    => 'Үнэ цэнэ',
                            'edit'     => 'Засварлах',
                            'delete'   => 'Устгах',
                        ],

                        'create'     => [
                            'title'    => 'Үл хөдлөх хөрөнгө үүсгэх',
                            'name'     => 'Нэр',
                            'type'     => 'Төрөл',
                            'language' => 'Хэл',
                            'value'    => 'Үнэ цэнэ',
                            'save-btn' => 'Хадгалах',
                        ],
                        'edit' => [
                            'title' => 'Өмчийг засах',
                        ],
                        'delete-success' => 'Хөрөнгийн өмчийг амжилттай устгалаа',
                        'create-success' => 'Хөрөнгийн өмчийг амжилттай үүсгэсэн',
                        'update-success' => 'Хөрөнгийн өмчийг амжилттай шинэчилсэн',
                    ],
                ],
                'comments' => [
                    'index'  => 'Сэтгэгдэл нэмэх',

                    'create' => [
                        'create-success' => 'Сэтгэгдэл амжилттай нэмэгдсэн',
                    ],

                    'post-comment' => 'Сэтгэгдэл бичих',
                    'post-reply'   => 'Хариу бичих',
                    'reply'        => 'Хариулах',
                    'add-reply'    => 'Хариу нэмэх',
                    'add-comment'  => 'Сэтгэгдэл нэмэх',
                    'no-comments'  => 'Одоогоор сэтгэгдэл алга',

                ],
                'edit' => [
                    'title'              => 'Хөрөнгийг засах',
                    'name'               => 'Нэр',
                    'value'              => 'Үнэ цэнэ',
                    'back-btn'           => 'Буцах',
                    'save-btn'           => 'Хадгалах',
                    'embedded_meta_info' => 'Суулгасан мета мэдээлэл',
                    'custom_meta_info'   => 'Тусгай мета мэдээлэл',
                    'tags'               => 'Шошго',
                    'select-tags'        => 'Таг сонгох эсвэл үүсгэх',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Лавлах зам',
                    'add_tags'           => 'Шошго нэмэх',
                    'tab'                => [
                        'preview'          => 'Урьдчилан үзэх',
                        'properties'       => 'Үл хөдлөх хөрөнгө',
                        'comments'         => 'Сэтгэгдэл',
                        'linked_resources' => 'Холбоотой эх сурвалжууд',
                        'history'          => 'Түүх',
                    ],
                    'button' => [
                        'download'        => 'Татаж авах',
                        'custom_download' => 'Захиалгат татаж авах',
                        'rename'          => 'Нэрээ өөрчлөх',
                        're_upload'       => 'Дахин байршуулах',
                        'delete'          => 'Устгах',
                    ],

                    'custom-download' => [
                        'title'              => 'Захиалгат татаж авах',
                        'format'             => 'Формат',
                        'width'              => 'Өргөн (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Өндөр (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Татаж авах',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Жинхэнэ',
                        ],
                    ],

                    'tag-already-exists'        => 'Шошго аль хэдийн байна',
                    'image-source-not-readable' => 'Зургийн эх сурвалжийг унших боломжгүй',
                    'failed-to-read'            => 'Зургийн мета өгөгдлийг уншиж чадсангүй:exception',
                    'file_re_upload_success'    => 'Файлуудыг амжилттай дахин байршууллаа.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Бүтээгдэхүүн',
                            'category'      => 'Ангилал',
                            'product-sku'   => 'Бүтээгдэхүүний үнэ: ',
                            'category code' => 'Ангиллын код: ',
                            'resource-type' => 'Нөөцийн төрөл',
                            'resource'      => 'Нөөц',
                            'resource-view' => 'Нөөц харах',
                        ],
                    ],
                ],
                'delete-success'                          => 'Өмчийг амжилттай устгалаа',
                'delete-failed-due-to-attached-resources' => 'Өмчийг нөөцөд холбосон тул устгаж чадсангүй (Хөрөнгийн нэр: :assetNames)',

                'datagrid'                                => [
                    'mass-delete-success'  => 'Массыг амжилттай устгасан.',
                    'files_upload_success' => 'Файлуудыг амжилттай байршууллаа.',
                    'file_upload_success'  => 'Файлыг амжилттай байршууллаа.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Хөрөнгө',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Хөрөнгө',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'ДАМ',
            'asset'            => 'Хөрөнгө',
            'property'         => 'Өмч',
            'comment'          => 'Сэтгэгдэл',
            'linked_resources' => 'Холбоотой нөөцүүд',
            'directory'        => 'Лавлах',
            'tag'              => 'Tag',
            'create'           => 'Үүсгэх',
            'edit'             => 'Засварлах',
            'update'           => 'Шинэчлэх',
            'delete'           => 'Устгах',
            'list'             => 'Жагсаалт',
            'view'             => 'Харах',
            'upload'           => 'Байршуулах',
            're_upload'        => 'Дахин байршуулах',
            'mass_update'      => 'Масс шинэчлэл',
            'mass_delete'      => 'Масс устгах',
            'download'         => 'Татаж авах',
            'custom_download'  => 'Захиалгат татаж авах',
            'rename'           => 'Нэрээ өөрчлөх',
            'move'             => 'Хөдлөх',
            'copy'             => 'Хуулах',
            'copy-structure'   => 'Лавлах бүтцийг хуулах',
            'download-zip'     => 'Zip татаж авах',
            'asset-assign'     => 'Хөрөнгө оноох',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute талбар шаардлагатай.',
            ],

            'comment' => [
                'required' => 'Сэтгэгдэл бичих шаардлагатай.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Нэрийн талбарыг оруулах шаардлагатай.',
                    'unique'   => 'Нэрийг нь аль хэдийн авсан.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Энэ үйлдэл нь зөвшөөрөлгүй юм.',
        ],
    ],
];
