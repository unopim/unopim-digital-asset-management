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
                    'message' => 'Видалення цього каталогу також призведе до видалення всіх підкаталогів усередині нього. Ця дія є остаточною і не може бути скасована.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Додати актив',
                    'assign-assets' => 'Призначити активи',
                    'assign'        => 'Призначити',
                    'preview-asset' => 'Попередній перегляд активу',
                    'preview'       => 'Попередній перегляд',
                    'remove'        => 'Видалити',
                    'download'      => 'Завантажити',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Назва файлу',
                    'tags'           => 'Теги',
                    'property-name'  => 'Назва властивості',
                    'property-value' => 'Значення властивості',
                    'created-at'     => 'Дата створення',
                    'updated-at'     => 'Дата оновлення',
                    'extension'      => 'Розширення',
                    'path'           => 'Шлях',
                    'size'           => 'Розмір',
                ],

                'directory' => [
                    'title'  => 'Каталог',
                    'create' => [
                        'title'    => 'Створити каталог',
                        'name'     => 'Назва',
                        'save-btn' => 'Зберегти каталог',
                    ],

                    'rename' => [
                        'title' => 'Перейменувати каталог',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Перейменувати актив',
                            'save-btn' => 'Зберегти актив',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Видалити',
                        'rename'                    => 'Перейменувати',
                        'copy'                      => 'Копіювати',
                        'download'                  => 'Завантажити',
                        'download-zip'              => 'Завантажити Zip',
                        'paste'                     => 'Вставити',
                        'add-directory'             => 'Додати каталог',
                        'upload-files'              => 'Завантажити файли',
                        'copy-directory-structured' => 'Копіювати структуру каталогу',
                        'get-by-id'                 => 'Отримати за ID',
                        'comment'                   => 'Коментар',
                    ],

                    'linked-resources'                          => 'Пов\'язані ресурси',
                    'not-found'                                 => 'Каталог не знайдено',
                    'created-success'                           => 'Каталог успішно створено',
                    'updated-success'                           => 'Каталог успішно оновлено',
                    'moved-success'                             => 'Каталог успішно переміщено',
                    'fetch-all-success'                         => 'Каталоги успішно отримано',
                    'can-not-deleted'                           => 'Каталог не може бути видалений, оскільки він є кореневим.',
                    'deleting-in-progress'                      => 'Видалення каталогу триває',
                    'can-not-copy'                              => 'Каталог не може бути скопійований, оскільки він є кореневим.',
                    'coping-in-progress'                        => 'Копіювання структури каталогу триває.',
                    'asset-not-found'                           => 'Актив не знайдено',
                    'asset-renamed-success'                     => 'Актив успішно перейменовано',
                    'asset-moved-success'                       => 'Актив успішно переміщено',
                    'asset-name-already-exist'                  => 'Нова назва вже існує в іншого активу з іменем :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Назва активу конфліктує з існуючим файлом у тому самому каталозі.',
                    'old-file-not-found'                        => 'Файл, запитаний за шляхом :old_path, не знайдено.',
                    'image-name-is-the-same'                    => 'Ця назва вже існує. Будь ласка, введіть іншу.',
                    'not-writable'                              => 'Вам не дозволено :actionType :type у цьому розташуванні ":path".',
                    'empty-directory'                           => 'Цей каталог порожній.',
                    'failed-download-directory'                 => 'Не вдалося створити zip файл.',
                    'not-allowed'                               => 'Завантаження скриптових файлів заборонено.',
                ],

                'title'       => 'DAM',
                'description' => 'Інструмент, який допоможе вам організувати, зберігати та керувати всіма медіа-активами в одному місці',
                'root'        => 'Корінь',
                'upload'      => 'Завантажити',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Властивості активу',
                        'create-btn' => 'Створити властивість',

                        'datagrid' => [
                            'name'     => 'Назва',
                            'type'     => 'Тип',
                            'language' => 'Мова',
                            'value'    => 'Значення',
                            'edit'     => 'Редагувати',
                            'delete'   => 'Видалити',
                        ],

                        'create' => [
                            'title'    => 'Створити властивість',
                            'name'     => 'Назва',
                            'type'     => 'Тип',
                            'language' => 'Мова',
                            'value'    => 'Значення',
                            'save-btn' => 'Зберегти',
                        ],
                        'edit' => [
                            'title' => 'Редагувати властивість',
                        ],
                        'delete-success' => 'Властивість активу успішно видалено',
                        'create-success' => 'Властивість активу успішно створено',
                        'update-success' => 'Властивість активу успішно оновлено',
                        'not-found'      => 'Властивість не знайдено',
                        'found-success'  => 'Властивість успішно знайдено',
                    ],
                ],
                'comments' => [
                    'index'  => 'Додати коментар',
                    'create' => [
                        'create-success' => 'Коментар успішно додано',
                        'create-failure' => 'Не вдалося створити коментар',
                    ],
                    'post-comment'    => 'Опублікувати коментар',
                    'post-reply'      => 'Опублікувати відповідь',
                    'reply'           => 'Відповісти',
                    'add-reply'       => 'Додати відповідь',
                    'add-comment'     => 'Додати коментар',
                    'no-comments'     => 'Коментарів поки немає',
                    'not-found'       => 'Коментарі не знайдено',
                    'updated-success' => 'Коментар успішно оновлено',
                    'update-failed'   => 'Не вдалося оновити коментар',
                    'delete-success'  => 'Коментар активу успішно видалено',
                    'delete-failed'   => 'Не вдалося видалити коментар активу',
                ],
                'edit' => [
                    'title'              => 'Редагувати актив',
                    'name'               => 'Назва',
                    'value'              => 'Значення',
                    'back-btn'           => 'Назад',
                    'save-btn'           => 'Зберегти',
                    'embedded_meta_info' => 'Вбудована метаінформація',
                    'custom_meta_info'   => 'Користувацька метаінформація',
                    'tags'               => 'Теги',
                    'select-tags'        => 'Оберіть або створіть тег',
                    'tag'                => 'Тег',
                    'directory-path'     => 'Шлях до каталогу',
                    'add_tags'           => 'Додати теги',
                    'tab'                => [
                        'preview'          => 'Попередній перегляд',
                        'properties'       => 'Властивості',
                        'comments'         => 'Коментарі',
                        'linked_resources' => 'Пов\'язані ресурси',
                        'history'          => 'Історія',
                    ],
                    'button' => [
                        'download'        => 'Завантажити',
                        'custom_download' => 'Користувацьке завантаження',
                        'rename'          => 'Перейменувати',
                        're_upload'       => 'Завантажити знову',
                        'delete'          => 'Видалити',
                    ],

                    'custom-download' => [
                        'title'              => 'Користувацьке завантаження',
                        'format'             => 'Формат',
                        'width'              => 'Ширина (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Висота (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Завантажити',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Оригінал',
                        ],
                    ],

                    'tag-already-exists'        => 'Тег уже існує',
                    'image-source-not-readable' => 'Джерело зображення не читається',
                    'failed-to-read'            => 'Не вдалося прочитати метадані зображення :exception',
                    'file-re-upload-success'    => 'Файли успішно завантажено повторно.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Продукт',
                            'category'      => 'Категорія',
                            'product-sku'   => 'Sku продукту: ',
                            'category code' => 'Код категорії: ',
                            'resource-type' => 'Тип ресурсу',
                            'resource'      => 'Ресурс',
                            'resource-view' => 'Перегляд ресурсу',
                        ],
                    ],
                    'found-success' => 'Ресурс успішно знайдено',
                    'not-found'     => 'Ресурс не знайдено',
                ],
                'tags' => [
                    'index'  => 'Додати теги',
                    'create' => [
                        'create-success' => 'Теги успішно додано',
                        'create-failure' => 'Не вдалося створити теги',
                    ],

                    'no-comments'    => 'Тегів ще немає',
                    'found-success'  => 'Тег успішно знайдено',
                    'not-found'      => 'Теги не знайдено',
                    'update-success' => 'Теги успішно оновлено',
                    'update-failed'  => 'Не вдалося оновити теги',
                    'delete-success' => 'Теги активу успішно видалено',
                    'delete-failed'  => 'Не вдалося видалити теги активу',
                ],
                'delete-success'                          => 'Актив успішно видалено',
                'delete-failed-due-to-attached-resources' => 'Актив використовується. Від\'єднайте перед видаленням',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Масове видалення пройшло успішно.',
                    'files-upload-success'                => 'Файли успішно завантажено.',
                    'file-upload-success'                 => 'Файл успішно завантажено.',
                    'not-found'                           => 'Файл не знайдено',
                    'edit-success'                        => 'Файл успішно завантажено',
                    'show-success'                        => 'Файл успішно знайдено',
                    'update-success'                      => 'Файл успішно оновлено',
                    'not-found-to-update'                 => 'Файл не існує',
                    'not-found-to-destroy'                => 'Файл не існує',
                    'files-upload-failed'                 => 'Не вдалося завантажити файли.',
                    'file-upload-failed'                  => 'Не вдалося завантажити файл',
                    'invalid-file'                        => 'Надано недійсний файл',
                    'invalid-file-format'                 => 'Недійсний формат',
                    'invalid-file-format-or-not-provided' => 'Файли не надано або недійсний формат.',
                    'download-image-failed'               => 'Не вдалося завантажити зображення за URL',
                    'file-process-failed'                 => 'Не вдалося обробити деякі файли',
                    'file-forbidden-type'                 => 'Файл має заборонений тип або розширення.',
                    'file-too-large'                      => 'Файл занадто великий. Максимально допустимий розмір: :size.',
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
            'property'         => 'Властивість',
            'comment'          => 'Коментар',
            'linked_resources' => 'Пов\'язані ресурси',
            'directory'        => 'Каталог',
            'tag'              => 'Тег',
            'create'           => 'Створити',
            'edit'             => 'Редагувати',
            'update'           => 'Оновити',
            'delete'           => 'Видалити',
            'list'             => 'Список',
            'view'             => 'Перегляд',
            'upload'           => 'Завантажити',
            're_upload'        => 'Завантажити знову',
            'mass_update'      => 'Масове оновлення',
            'mass_delete'      => 'Масове видалення',
            'download'         => 'Завантажити',
            'custom_download'  => 'Користувацьке завантаження',
            'rename'           => 'Перейменувати',
            'move'             => 'Перемістити',
            'copy'             => 'Копіювати',
            'copy-structure'   => 'Копіювати структуру каталогу',
            'download-zip'     => 'Завантажити Zip',
            'asset-assign'     => 'Призначити актив',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Поле :attribute є обов\'язковим.',
            ],

            'comment' => [
                'required' => 'Повідомлення коментаря є обов\'язковим.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Поле тегу є обов\'язковим.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Поле назви є обов\'язковим.',
                    'unique'   => 'Ця назва вже зайнята.',
                ],
                'language' => [
                    'not-found' => 'Обрану мову не знайдено або вона зараз вимкнена.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Ця дія не авторизована.',
        ],
    ],
];
