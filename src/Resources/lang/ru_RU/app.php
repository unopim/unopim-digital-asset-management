<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'ЦУМ',
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'Удаление этой директории приведет к удалению всех вложенных поддиректорий. Это действие необратимо.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Добавить медиа',
                    'assign-assets' => 'Назначить медиа',
                    'assign'        => 'Назначить',
                    'preview-asset' => 'Предпросмотр медиа',
                    'preview'       => 'Предпросмотр',
                    'remove'        => 'Удалить',
                    'download'      => 'Скачать',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'ЦУМ',

                'datagrid' => [
                    'file-name'      => 'Имя файла',
                    'tags'           => 'Теги',
                    'property-name'  => 'Имя свойства',
                    'property-value' => 'Значение свойства',
                    'created-at'     => 'Создано',
                    'updated-at'     => 'Обновлено',
                    'extension'      => 'Расширение',
                    'path'           => 'Путь',
                ],

                'directory' => [
                    'title'        => 'Директория',
                    'create'       => [
                        'title'    => 'Создать директорию',
                        'name'     => 'Имя',
                        'save-btn' => 'Сохранить директорию',
                    ],

                    'rename' => [
                        'title' => 'Переименовать директорию',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Переименовать медиа',
                            'save-btn' => 'Сохранить медиа',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Удалить',
                        'rename'                    => 'Переименовать',
                        'copy'                      => 'Копировать',
                        'download'                  => 'Скачать',
                        'download-zip'              => 'Скачать ZIP',
                        'paste'                     => 'Вставить',
                        'add-directory'             => 'Добавить директорию',
                        'upload-files'              => 'Загрузить файлы',
                        'copy-directory-structured' => 'Копировать структуру директории',
                    ],

                    'not-found'                                 => 'Директория не найдена',
                    'created-success'                           => 'Директория успешно создана',
                    'updated-success'                           => 'Директория успешно обновлена',
                    'moved-success'                             => 'Директория успешно перемещена',
                    'can-not-deleted'                           => 'Директория не может быть удалена, так как это корневая директория.',
                    'deleting-in-progress'                      => 'Удаление директории выполняется...',
                    'can-not-copy'                              => 'Директория не может быть скопирована, так как это корневая директория.',
                    'coping-in-progress'                        => 'Копирование структуры директории выполняется...',
                    'asset-not-found'                           => 'Медиа не найдено',
                    'asset-renamed-success'                     => 'Медиа успешно переименовано',
                    'asset-moved-success'                       => 'Медиа успешно перемещено',
                    'asset-name-already-exist'                  => 'Новое имя уже существует с другим медиа :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Имя медиа конфликтует с существующим файлом в той же директории.',
                    'old-file-not-found'                        => 'Файл по пути :old_path не найден.',
                    'image-name-is-the-same'                    => 'Это имя уже существует. Пожалуйста, введите другое.',
                    'not-writable'                              => 'Вам не разрешено :actionType :type в данном расположении ":path".',
                    'empty-directory'                           => 'Эта директория пуста.',
                    'failed-download-directory'                 => 'Не удалось создать ZIP файл.',
                ],

                'title'       => 'ЦУМ',
                'description' => 'Этот инструмент помогает организовать, хранить и управлять всеми вашими медиа в одном месте.',
                'root'        => 'Корень',
                'upload'      => 'Загрузить',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Свойства активов',
                        'create-btn' => 'Создать свойство',

                        'datagrid'      => [
                            'name'     => 'Имя',
                            'type'     => 'Тип',
                            'language' => 'Язык',
                            'value'    => 'Значение',
                            'edit'     => 'Изменить',
                            'delete'   => 'Удалить',
                        ],

                        'create'     => [
                            'title'    => 'Создать свойство',
                            'name'     => 'Имя',
                            'type'     => 'Тип',
                            'language' => 'Язык',
                            'value'    => 'Значение',
                            'save-btn' => 'Сохранить',
                        ],
                        'edit' => [
                            'title' => 'Редактировать свойство',
                        ],
                        'delete-success' => 'Свойство актива успешно удалено',
                        'create-success' => 'Свойство актива успешно создано',
                        'update-success' => 'Свойство актива успешно обновлено',
                    ],
                ],
                'comments' => [
                    'index'  => 'Добавить комментарий',
                    'create' => [
                        'create-success' => 'Комментарий был успешно добавлен',
                    ],
                    'post-comment' => 'Опубликовать комментарий',
                    'post-reply'   => 'Опубликовать ответ',
                    'reply'        => 'Ответить',
                    'add-reply'    => 'Добавить ответ',
                    'add-comment'  => 'Добавить комментарий',
                    'no-comments'  => 'Пока нет комментариев',

                ],
                'edit' => [
                    'title'              => 'Редактировать актив',
                    'name'               => 'Имя',
                    'value'              => 'Значение',
                    'back-btn'           => 'Назад',
                    'save-btn'           => 'Сохранить',
                    'embedded_meta_info' => 'Встроенная метаинформация',
                    'custom_meta_info'   => 'Пользовательская метаинформация',
                    'tags'               => 'Теги',
                    'select-tags'        => 'Выбрать или создать тег',
                    'tag'                => 'Тег',
                    'directory-path'     => 'Путь к каталогу',
                    'add_tags'           => 'Добавить теги',
                    'tab'                => [
                        'preview'          => 'Предварительный просмотр',
                        'properties'       => 'Свойства',
                        'comments'         => 'Комментарии',
                        'linked_resources' => 'Связанные ресурсы',
                        'history'          => 'История',
                    ],
                    'button' => [
                        'download'        => 'Скачать',
                        'custom_download' => 'Пользовательская загрузка',
                        'rename'          => 'Переименовать',
                        're_upload'       => 'Повторная загрузка',
                        'delete'          => 'Удалить',
                    ],

                    'custom-download' => [
                        'title'              => 'Пользовательская загрузка',
                        'format'             => 'Формат',
                        'width'              => 'Ширина (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Высота (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Загрузка',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Оригинал',
                        ],
                    ],

                    'tag-already-exists'        => 'Тег уже существует',
                    'image-source-not-readable' => 'Источник изображения не может быть прочитан',
                    'failed-to-read'            => 'Не удалось прочитать метаданные изображения :exception',
                    'file_re_upload_success'    => 'Файлы успешно повторно загружены.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Продукт',
                            'category'      => 'Категория',
                            'product-sku'   => 'Артикул продукта: ',
                            'category code' => 'Код категории: ",',
                            'resource-type' => 'Тип ресурса',
                            'resource'      => 'Ресурс',
                            'resource-view' => 'Вид ресурса',
                        ],
                    ],
                ],
                'delete-success'                          => 'Актив успешно удален',
                'delete-failed-due-to-attached-resources' => 'Не удалось удалить актив, так как он связан с ресурсами (Имя актива: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Массовое удаление успешно выполнено.',
                    'files_upload_success' => 'Файлы успешно загружены.',
                    'file_upload_success'  => 'Файл успешно загружен.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Объект',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Объект',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'ЦУМ',
            'asset'            => 'Актив',
            'property'         => 'Свойство',
            'comment'          => 'Комментарий',
            'linked_resources' => 'Связанные ресурсы',
            'directory'        => 'Каталог',
            'tag'              => 'Тег',
            'create'           => 'Создать',
            'edit'             => 'Изменить',
            'update'           => 'Обновить',
            'delete'           => 'Удалить',
            'list'             => 'Список',
            'view'             => 'Просмотр',
            'upload'           => 'Загрузить',
            're_upload'        => 'Повторная загрузка',
            'mass_update'      => 'Массовое обновление',
            'mass_delete'      => 'Массовое удаление',
            'download'         => 'Загрузить',
            'custom_download'  => 'Пользовательская загрузка',
            'rename'           => 'Переименовать',
            'move'             => 'Переместить',
            'copy'             => 'Копировать',
            'copy-structure'   => 'Копировать структуру каталога',
            'download-zip'     => 'Загрузить Zip',
            'asset-assign'     => 'Назначить актив',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Поле :attribute обязательно.',
            ],

            'comment' => [
                'required' => 'Поле «Комментарий» обязательно.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Поле «Имя» обязательно.',
                    'unique'   => 'Имя уже занято.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Это действие является несанкционированным.',
        ],
    ],
];
