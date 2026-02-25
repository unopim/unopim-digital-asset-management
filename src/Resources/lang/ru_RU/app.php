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
                    'message' => 'Удаление этой директории также удалит все поддиректории внутри. Это действие является постоянным и не может быть отменено.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Добавить актив',
                    'assign-assets' => 'Назначить активы',
                    'assign'        => 'Назначить',
                    'preview-asset' => 'Предпросмотр актива',
                    'preview'       => 'Предпросмотр',
                    'remove'        => 'Удалить',
                    'download'      => 'Скачать',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Название файла',
                    'tags'           => 'Теги',
                    'property-name'  => 'Название свойства',
                    'property-value' => 'Значение свойства',
                    'created-at'     => 'Дата создания',
                    'updated-at'     => 'Дата обновления',
                    'extension'      => 'Расширение',
                    'path'           => 'Путь',
                    'size'           => 'Размер',
                ],

                'directory' => [
                    'title'        => 'Директория',
                    'create'       => [
                        'title'    => 'Создать директорию',
                        'name'     => 'Название',
                        'save-btn' => 'Сохранить директорию',
                    ],

                    'rename' => [
                        'title' => 'Переименовать директорию',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Переименовать актив',
                            'save-btn' => 'Сохранить актив',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Удалить',
                        'rename'                    => 'Переименовать',
                        'copy'                      => 'Копировать',
                        'download'                  => 'Скачать',
                        'download-zip'              => 'Скачать Zip',
                        'paste'                     => 'Вставить',
                        'add-directory'             => 'Добавить директорию',
                        'upload-files'              => 'Загрузить файлы',
                        'copy-directory-structured' => 'Копировать структуру директории',
                    ],

                    'not-found'                                 => 'Директория не найдена',
                    'created-success'                           => 'Директория успешно создана',
                    'updated-success'                           => 'Директория успешно обновлена',
                    'moved-success'                             => 'Директория успешно перемещена',
                    'fetch-all-success'                         => 'Директории успешно загружены',
                    'can-not-deleted'                           => 'Директория не может быть удалена, так как она является корневой.',
                    'deleting-in-progress'                      => 'Удаление директории в процессе',
                    'can-not-copy'                              => 'Директория не может быть скопирована, так как она является корневой.',
                    'coping-in-progress'                        => 'Копирование структуры директории в процессе.',
                    'asset-not-found'                           => 'Актив не найден',
                    'asset-renamed-success'                     => 'Актив успешно переименован',
                    'asset-moved-success'                       => 'Актив успешно перемещен',
                    'asset-name-already-exist'                  => 'Новое имя уже существует у другого актива с именем :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Имя актива конфликтует с существующим файлом в той же директории.',
                    'old-file-not-found'                        => 'Запрашиваемый файл по пути :old_path не найден.',
                    'image-name-is-the-same'                    => 'Это имя уже существует. Пожалуйста, введите другое.',
                    'not-writable'                              => 'Вам не разрешено :actionType :type в этом местоположении ":path".',
                    'empty-directory'                           => 'Эта директория пуста.',
                    'failed-download-directory'                 => 'Не удалось создать zip файл.',
                    'not-allowed'                               => 'Загрузка скриптовых файлов не разрешена.',
                ],

                'title'       => 'DAM',
                'description' => 'Этот инструмент поможет вам организовать, хранить и управлять всеми вашими медиа-активами в одном месте',
                'root'        => 'Корень',
                'upload'      => 'Загрузить',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Свойства актива',
                        'create-btn' => 'Создать свойство',

                        'datagrid'      => [
                            'name'     => 'Название',
                            'type'     => 'Тип',
                            'language' => 'Язык',
                            'value'    => 'Значение',
                            'edit'     => 'Редактировать',
                            'delete'   => 'Удалить',
                        ],

                        'create'     => [
                            'title'    => 'Создать свойство',
                            'name'     => 'Название',
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
                        'not-found'      => 'Свойство не найдено',
                        'found-success'  => 'Свойство успешно найдено',

                    ],
                ],
                'comments' => [
                    'index'  => 'Добавить комментарий',
                    'create' => [
                        'create-success' => 'Комментарий успешно добавлен',
                        'create-failure' => 'Не удалось создать комментарий',
                    ],
                    'post-comment'    => 'Опубликовать комментарий',
                    'post-reply'      => 'Ответить',
                    'reply'           => 'Ответить',
                    'add-reply'       => 'Добавить ответ',
                    'add-comment'     => 'Добавить комментарий',
                    'no-comments'     => 'Комментарии пока отсутствуют',
                    'not-found'       => 'Комментарии не найдены',
                    'updated-success' => 'Комментарий успешно обновлен',
                    'update-failed'   => 'Не удалось обновить комментарий',
                    'delete-success'  => 'Комментарий успешно удален',
                    'delete-failed'   => 'Не удалось удалить комментарий',
                ],
                'edit' => [
                    'title'              => 'Редактировать актив',
                    'name'               => 'Название',
                    'value'              => 'Значение',
                    'back-btn'           => 'Назад',
                    'save-btn'           => 'Сохранить',
                    'embedded_meta_info' => 'Встроенная метаинформация',
                    'custom_meta_info'   => 'Пользовательская метаинформация',
                    'tags'               => 'Теги',
                    'select-tags'        => 'Выберите или создайте тег',
                    'tag'                => 'Тег',
                    'directory-path'     => 'Путь к директории',
                    'add_tags'           => 'Добавить теги',
                    'tab'                => [
                        'preview'          => 'Предпросмотр',
                        'properties'       => 'Свойства',
                        'comments'         => 'Комментарии',
                        'linked_resources' => 'Связанные ресурсы',
                        'history'          => 'История',
                    ],
                    'button' => [
                        'download'        => 'Скачать',
                        'custom_download' => 'Пользовательская загрузка',
                        'rename'          => 'Переименовать',
                        're_upload'       => 'Загрузить снова',
                        'delete'          => 'Удалить',
                    ],

                    'custom-download' => [
                        'title'              => 'Пользовательская загрузка',
                        'format'             => 'Формат',
                        'width'              => 'Ширина (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Высота (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Скачать',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Оригинал',
                        ],
                    ],

                    'tag-already-exists'        => 'Тег уже существует',
                    'image-source-not-readable' => 'Источник изображения не читаем',
                    'failed-to-read'            => 'Не удалось прочитать метаданные изображения :exception',
                    'file-re-upload-success'    => 'Файлы успешно загружены повторно.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Продукт',
                            'category'      => 'Категория',
                            'product-sku'   => 'Артикул продукта: ',
                            'category code' => 'Код категории: ',
                            'resource-type' => 'Тип ресурса',
                            'resource'      => 'Ресурс',
                            'resource-view' => 'Просмотр ресурса',
                        ],
                    ],
                    'found-success' => 'Ресурс успешно найден',
                    'not-found'     => 'Ресурс не найден',
                ],
                'tags' => [
                    'index'  => 'Добавить теги',
                    'create' => [
                        'create-success' => 'Теги успешно добавлены',
                        'create-failure' => 'Не удалось создать теги',
                    ],

                    'no-comments'    => 'Теги ещё не добавлены',
                    'found-success'  => 'Тег успешно найден',
                    'not-found'      => 'Теги не найдены',
                    'update-success' => 'Теги успешно обновлены',
                    'update-failed'  => 'Не удалось обновить теги',
                    'delete-success' => 'Теги ресурса успешно удалены',
                    'delete-failed'  => 'Не удалось удалить теги актива',
                ],
                'delete-success'                          => 'Актив успешно удален',
                'delete-failed-due-to-attached-resources' => 'Ресурс используется. Отвяжите перед удалением',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Массовое удаление прошло успешно.',
                    'files-upload-success'                => 'Файлы успешно загружены.',
                    'file-upload-success'                 => 'Файл успешно загружен.',
                    'not-found'                           => 'Файл не найден',
                    'edit-success'                        => 'Файл успешно загружен',
                    'show-success'                        => 'Файл успешно найден',
                    'update-success'                      => 'Файл успешно обновлен',
                    'not-found-to-update'                 => 'Файл не существует',
                    'not-found-to-destroy'                => 'Файл не существует',
                    'files-upload-failed'                 => 'Не удалось загрузить файлы.',
                    'file-upload-failed'                  => 'Не удалось загрузить файл',
                    'invalid-file'                        => 'Предоставлен неверный файл',
                    'invalid-file-format'                 => 'Неверный формат',
                    'invalid-file-format-or-not-provided' => 'Файлы не предоставлены или неверный формат.',
                    'download-image-failed'               => 'Не удалось загрузить изображение по URL',
                    'file-process-failed'                 => 'Некоторые файлы не удалось обработать',
                    'file-forbidden-type'                 => 'Файл имеет запрещенный тип или расширение.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Актив',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Актив',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Актив',
            'property'         => 'Свойство',
            'comment'          => 'Комментарий',
            'linked_resources' => 'Связанные ресурсы',
            'directory'        => 'Директория',
            'tag'              => 'Тег',
            'create'           => 'Создать',
            'edit'             => 'Редактировать',
            'update'           => 'Обновить',
            'delete'           => 'Удалить',
            'list'             => 'Список',
            'view'             => 'Просмотр',
            'upload'           => 'Загрузить',
            're_upload'        => 'Загрузить снова',
            'mass_update'      => 'Массовое обновление',
            'mass_delete'      => 'Массовое удаление',
            'download'         => 'Скачать',
            'custom_download'  => 'Пользовательская загрузка',
            'rename'           => 'Переименовать',
            'move'             => 'Переместить',
            'copy'             => 'Копировать',
            'copy-structure'   => 'Копировать структуру директории',
            'download-zip'     => 'Скачать Zip',
            'asset-assign'     => 'Назначить актив',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Поле :attribute обязательно для заполнения.',
            ],

            'comment' => [
                'required' => 'Сообщение комментария обязательно.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Поле тега обязательно для заполнения.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Поле имени обязательно для заполнения.',
                    'unique'   => 'Имя уже занято.',
                ],
                'language' => [
                    'not-found' => 'Выбранный язык не найден или в данный момент отключён.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Это действие не авторизовано.',
        ],
    ],
];
