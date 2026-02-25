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
                    'message' => 'Энэхүү директорийг устгаснаар түүнд агуулагдах бүх дэд директорууд устгагдана. Энэ үйлдэл нь байнга үйлдэгдэж, сэргээх боломжгүй болно.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Хэрэглэгдэхүүн нэмэх',
                    'assign-assets' => 'Хэрэглэгдэхүүн хуваарилах',
                    'assign'        => 'Хуваарилах',
                    'preview-asset' => 'Хэрэглэгдэхүүн урьдчилан харах',
                    'preview'       => 'Урдчилсан харах',
                    'remove'        => 'Хасах',
                    'download'      => 'Татаж авах',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Файлын нэр',
                    'tags'           => 'Таг',
                    'property-name'  => 'Ү propertys нэр',
                    'property-value' => 'Ү propertys утга',
                    'created-at'     => 'Үүсгэсэн огноо',
                    'updated-at'     => 'Шинэчлэгдсэн огноо',
                    'extension'      => 'Өргөтгөл',
                    'path'           => 'Зам',
                    'size'           => 'Хэмжээ',
                ],

                'directory' => [
                    'title'        => 'Директор',
                    'create'       => [
                        'title'    => 'Директор үүсгэх',
                        'name'     => 'Нэр',
                        'save-btn' => 'Директорыг хадгалах',
                    ],

                    'rename' => [
                        'title' => 'Директорыг нэрлэх',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Хэрэглэгдэхүүнийг нэрлэх',
                            'save-btn' => 'Хэрэглэгдэхүүн хадгалах',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Устгах',
                        'rename'                    => 'Нэрлэх',
                        'copy'                      => 'Хуулбарлах',
                        'download'                  => 'Татаж авах',
                        'download-zip'              => 'ZIP татаж авах',
                        'paste'                     => 'Б yapтах',
                        'add-directory'             => 'Директорыг нэмэх',
                        'upload-files'              => 'Файлуудыг ачаалах',
                        'copy-directory-structured' => 'Директорыг бүтцийн дагуу хуулбарлах',
                    ],

                    'not-found'                                 => 'Директор олдсонгүй',
                    'created-success'                           => 'Директор амжилттай үүсгэгдлээ',
                    'updated-success'                           => 'Директор амжилттай шинэчлэгдлээ',
                    'moved-success'                             => 'Директор амжилттай шилжигдлээ',
                    'fetch-all-success'                         => 'Директорыг амжилттай авч ирлээ',
                    'can-not-deleted'                           => 'Энэхүү директорийг устгаж болохгүй, учир нь энэ нь үндсэн директор юм.',
                    'deleting-in-progress'                      => 'Директорыг устгаж байна',
                    'can-not-copy'                              => 'Энэхүү директорийг хуулбарлаж болохгүй, учир нь энэ нь үндсэн директор юм.',
                    'coping-in-progress'                        => 'Директорыг бүтэцлэн хуулбарлаж байна.',
                    'asset-not-found'                           => 'Хэрэглэгдэхүүн олдсонгүй',
                    'asset-renamed-success'                     => 'Хэрэглэгдэхүүн амжилттай нэрлэгдлээ',
                    'asset-moved-success'                       => 'Хэрэглэгдэхүүн амжилттай шилжигдлээ',
                    'asset-name-already-exist'                  => 'Шинэ нэр аль хэдийн өөр нэг хэрэглэгдэхүүнтэй байгаа :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Хэрэглэгдэхүүний нэр нь тухайн директор дотор байгаа өөр файлын нэртэй зөрчилдөж байна.',
                    'old-file-not-found'                        => ':old_path зам дээр хүссэн файл олдсонгүй.',
                    'image-name-is-the-same'                    => 'Энэ нэр аль хэдийн байна. Өөр нэр оруулна уу.',
                    'not-writable'                              => 'Та ":path" байршилд :actionType хийх эрхгүй байна.',
                    'empty-directory'                           => 'Энэ директор хоосон байна.',
                    'failed-download-directory'                 => 'ZIP файл үүсгэхэд алдаа гарлаа.',
                    'not-allowed'                               => 'Скрипт файлуудыг ачаалуулахыг хориглоно.',
                ],

                'title'       => 'DAM',
                'description' => 'Тус хэрэгсэл нь таны бүх медиаг нэг газар зохион байгуулж, хадгалахад тусална',
                'root'        => 'Үндсэн',
                'upload'      => 'Ачуулах',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Хэрэглэгдэхүүний шинж чанарууд',
                        'create-btn' => 'Шинж чанар үүсгэх',

                        'datagrid'      => [
                            'name'     => 'Нэр',
                            'type'     => 'Төрөл',
                            'language' => 'Хэл',
                            'value'    => 'Утга',
                            'edit'     => 'Засварлах',
                            'delete'   => 'Устгах',
                        ],

                        'create'     => [
                            'title'    => 'Шинж чанар үүсгэх',
                            'name'     => 'Нэр',
                            'type'     => 'Төрөл',
                            'language' => 'Хэл',
                            'value'    => 'Утга',
                            'save-btn' => 'Хадгалах',
                        ],
                        'edit' => [
                            'title' => 'Шинж чанарыг засварлах',
                        ],
                        'delete-success' => 'Хэрэглэгдэхүүний шинж чанар амжилттай устгагдлаа',
                        'create-success' => 'Хэрэглэгдэхүүний шинж чанар амжилттай үүсгэгдлээ',
                        'update-success' => 'Хэрэглэгдэхүүний шинж чанар амжилттай шинэчлэгдлээ',
                        'not-found'      => 'Шинж чанар олдсонгүй',
                        'found-success'  => 'Шинж чанар амжилттай олдлоо',

                    ],
                ],
                'comments' => [
                    'index'  => 'Сэтгэгдэл нэмэх',
                    'create' => [
                        'create-success' => 'Сэтгэгдэл амжилттай нэмэгдлээ',
                        'create-failure' => 'Сэтгэгдэл үүсгэхэд алдаа гарлаа',
                    ],
                    'post-comment'    => 'Сэтгэгдэл бичих',
                    'post-reply'      => 'Хариу бичих',
                    'reply'           => 'Хариу',
                    'add-reply'       => 'Хариу нэмэх',
                    'add-comment'     => 'Сэтгэгдэл нэмэх',
                    'no-comments'     => 'Одоогоор сэтгэгдэл байхгүй',
                    'not-found'       => 'Сэтгэгдэл олдсонгүй',
                    'updated-success' => 'Сэтгэгдэл амжилттай шинэчлэгдлээ',
                    'update-failed'   => 'Сэтгэгдэл шинэчлэгдэхэд алдаа гарлаа',
                    'delete-success'  => 'Хэрэглэгдэхүүний сэтгэгдэл амжилттай устгагдлаа',
                    'delete-failed'   => 'Хэрэглэгдэхүүний сэтгэгдэл устгахад алдаа гарлаа',
                ],
                'edit' => [
                    'title'              => 'Хэрэглэгдэхүүнийг Засварлах',
                    'name'               => 'Нэр',
                    'value'              => 'Утга',
                    'back-btn'           => 'Буцах',
                    'save-btn'           => 'Хадгалах',
                    'embedded_meta_info' => 'Оролт Мета Мэдээлэл',
                    'custom_meta_info'   => 'Хувийн Мета Мэдээлэл',
                    'tags'               => 'Таг',
                    'select-tags'        => 'Таг Сонгох эсвэл Үүсгэх',
                    'tag'                => 'Таг',
                    'directory-path'     => 'Директорын Зам',
                    'add_tags'           => 'Таг Нэмэх',
                    'tab'                => [
                        'preview'          => 'Урдчилсан Харах',
                        'properties'       => 'Шинж Чанарууд',
                        'comments'         => 'Сэтгэгдлүүд',
                        'linked_resources' => 'Холбогдсон Нөөцүүд',
                        'history'          => 'Түүх',
                    ],
                    'button' => [
                        'download'        => 'Татаж Авах',
                        'custom_download' => 'Хувийн Татаж Авах',
                        'rename'          => 'Нэрлэх',
                        're_upload'       => 'Дахин Ачуулах',
                        'delete'          => 'Устгах',
                    ],

                    'custom-download' => [
                        'title'              => 'Хувийн Татаж Авах',
                        'format'             => 'Формат',
                        'width'              => 'Өргөн (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Өндөр (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Татаж Авах',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Эх хувилбар',
                        ],
                    ],

                    'tag-already-exists'        => 'Таг аль хэдийн байгаа',
                    'image-source-not-readable' => 'Зураг эх сурвалжийг уншиж чадахгүй',
                    'failed-to-read'            => 'Зурагны мета мэдээллийг уншихад алдаа гарлаа :exception',
                    'file-re-upload-success'    => 'Файлуудыг дахин амжилттай ачаалсан.',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Бүтээгдэхүүн',
                            'category'      => 'Ангилал',
                            'product-sku'   => 'Бүтээгдэхүүний SKU: ',
                            'category code' => 'Ангилалын Код: ',
                            'resource-type' => 'Нөөцийн Төрөл',
                            'resource'      => 'Нөөц',
                            'resource-view' => 'Нөөцийн Үзүүлэлт',
                        ],
                    ],
                    'found-success' => 'Нөөц амжилттай олдлоо',
                    'not-found'     => 'Нөөц олдсонгүй',
                ],
                'tags' => [
                    'index'  => 'Таг Нэмэх',
                    'create' => [
                        'create-success' => 'Таг амжилттай нэмэгдлээ',
                        'create-failure' => 'Таг үүсгэхэд алдаа гарлаа',
                    ],

                    'no-comments'    => 'Одоогоор Таг байхгүй',
                    'found-success'  => 'Таг амжилттай олдлоо',
                    'not-found'      => 'Таг олдсонгүй',
                    'update-success' => 'Таг амжилттай шинэчлэгдлээ',
                    'update-failed'  => 'Таг шинэчлэхэд алдаа гарлаа',
                    'delete-success' => 'Эзэмшигчийн шошгууд амжилттай устгагдлаа',
                    'delete-failed'  => 'Хэрэглэгдэхүүний таг устгахад алдаа гарлаа',
                ],
                'delete-success'                          => 'Хэрэглэгдэхүүн амжилттай устгагдлаа',
                'delete-failed-due-to-attached-resources' => 'Хөрөнгө ашиглагдаж байна. Устгахаас өмнө салгана уу',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Олон Файлууд Амжилттай Устгагдлаа.',
                    'files-upload-success'                => 'Файлууд амжилттай ачаалагдлаа.',
                    'file-upload-success'                 => 'Файл амжилттай ачаалагдлаа.',
                    'not-found'                           => 'Файл олдсонгүй',
                    'edit-success'                        => 'Файл амжилттай шинэчлэгдлээ',
                    'show-success'                        => 'Файл амжилттай олдлоо',
                    'update-success'                      => 'Файл амжилттай шинэчлэгдлээ',
                    'not-found-to-update'                 => 'Файл байхгүй',
                    'not-found-to-destroy'                => 'Файл байхгүй',
                    'files-upload-failed'                 => 'Файлуудыг ачааллахад алдаа гарлаа.',
                    'file-upload-failed'                  => 'Файл ачааллахад алдаа гарлаа',
                    'invalid-file'                        => 'Хүчингүй файл өгөгдсөн',
                    'invalid-file-format'                 => 'Хүчингүй формат',
                    'invalid-file-format-or-not-provided' => 'Файл өгөөгүй эсвэл хүчингүй формат.',
                    'download-image-failed'               => 'Зургийг URL-аас татаж авахад алдаа гарлаа',
                    'file-process-failed'                 => 'Зарим файлуудыг боловсруулахад алдаа гарлаа',
                    'file-forbidden-type'                 => 'Файл нь хориотой төрлийн эсвэл өргөтгөлтэй байна.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Хэрэглэгдэхүүн',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Хэрэглэгдэхүүн',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Хэрэглэгдэхүүн',
            'property'         => 'Шинж Чанар',
            'comment'          => 'Сэтгэгдэл',
            'linked_resources' => 'Холбогдсон Нөөцүүд',
            'directory'        => 'Директор',
            'tag'              => 'Таг',
            'create'           => 'Үүсгэх',
            'edit'             => 'Засварлах',
            'update'           => 'Шинэчлэх',
            'delete'           => 'Устгах',
            'list'             => 'Жагсаалт',
            'view'             => 'Үзэх',
            'upload'           => 'Ачуулах',
            're_upload'        => 'Дахин Ачуулах',
            'mass_update'      => 'Олон Шинэчлэлт',
            'mass_delete'      => 'Олон Устгалт',
            'download'         => 'Татаж Авах',
            'custom_download'  => 'Хувийн Татаж Авах',
            'rename'           => 'Нэрлэх',
            'move'             => 'Шилжүүлэх',
            'copy'             => 'Хуулах',
            'copy-structure'   => 'Директорыг Бүтцийн Дагуу Хуулах',
            'download-zip'     => 'ZIP Татаж Авах',
            'asset-assign'     => 'Хэрэглэгдэхүүн Хуваарилах',
        ],

        'validation' => [
            'asset' => [
                'required' => '":attribute" талбар шаардлагатай.',
            ],

            'comment' => [
                'required' => 'Сэтгэгдэл бичих мессеж шаардлагатай.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Таг талбар шаардлагатай.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Нэр талбар шаардлагатай.',
                    'unique'   => 'Нэр аль хэдийн авсан байна.',
                ],
                'language' => [
                    'not-found' => 'Сонгосон хэл олдсонгүй эсвэл одоогоор идэвхгүй байна.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Энэ үйлдэлд эрхгүй.',
        ],
    ],
];
