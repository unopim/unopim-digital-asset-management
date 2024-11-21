<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'سد',
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'سيؤدي حذف هذا الدليل إلى حذف جميع الدلائل الفرعية الموجودة بداخله أيضًا. هذا الإجراء دائم ولا يمكن التراجع عنه.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'إضافة أصل',
                    'assign-assets' => 'تعيين الأصول',
                    'assign'        => 'تعيين',
                    'preview-asset' => 'معاينة الأصل',
                    'preview'       => 'معاينة',
                    'remove'        => 'إزالة',
                    'download'      => 'تنزيل',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'سد',

                'datagrid' => [
                    'file-name'      => 'اسم الملف',
                    'tags'           => 'العلامات',
                    'property-name'  => 'اسم الخاصية',
                    'property-value' => 'قيمة الخاصية',
                    'created-at'     => 'تاريخ الإنشاء',
                    'updated-at'     => 'تاريخ التحديث',
                    'extension'      => 'الملحق',
                    'path'           => 'المسار',
                    'size'           => 'الحجم',
                ],

                'directory' => [
                    'title'  => 'الدليل',
                    'create' => [
                        'title'    => 'إنشاء دليل',
                        'name'     => 'الاسم',
                        'save-btn' => 'حفظ الدليل',
                    ],

                    'rename' => [
                        'title' => 'إعادة تسمية الدليل',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'إعادة تسمية الأصل',
                            'save-btn' => 'حفظ الأصل',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'حذف',
                        'rename'                    => 'إعادة تسمية',
                        'copy'                      => 'نسخ',
                        'download'                  => 'تنزيل',
                        'download-zip'              => 'تنزيل Zip',
                        'paste'                     => 'لصق',
                        'add-directory'             => 'إضافة دليل',
                        'upload-files'              => 'تحميل الملفات',
                        'copy-directory-structured' => 'نسخ هيكل الدليل',
                    ],

                    'not-found'                                 => 'لم يتم العثور على أي دليل',
                    'created-success'                           => 'تم إنشاء الدليل بنجاح',
                    'updated-success'                           => 'تم تحديث الدليل بنجاح',
                    'moved-success'                             => 'تم نقل الدليل بنجاح',
                    'can-not-deleted'                           => 'لا يمكن حذف الدليل لأنه الدليل الجذر.',
                    'deleting-in-progress'                      => 'جاري حذف الدليل',
                    'can-not-copy'                              => 'لا يمكن نسخ الدليل لأنه الدليل الجذر.',
                    'coping-in-progress'                        => 'جاري نسخ هيكل الدليل.',
                    'asset-not-found'                           => 'لم يتم العثور على أي أصل',
                    'asset-renamed-success'                     => 'تمت إعادة تسمية الأصل بنجاح',
                    'asset-moved-success'                       => 'تم نقل الأصل بنجاح',
                    'asset-name-already-exist'                  => 'الاسم الجديد موجود بالفعل مع أصل آخر باسم :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'يتعارض اسم الأصل مع ملف موجود في نفس الدليل.',
                    'old-file-not-found'                        => 'لم يتم العثور على الملف المطلوب في المسار :old_path.',
                    'image-name-is-the-same'                    => 'هذا الاسم موجود بالفعل. الرجاء إدخال اسم مختلف.',
                    'not-writable'                              => 'أنت غير مسموح لك بـ :actionType لـ :type في هذا الموقع ":path".',
                    'empty-directory'                           => 'هذا الدليل فارغ.',
                    'failed-download-directory'                 => 'فشل إنشاء ملف zip.',
                ],

                'title'       => 'سد',
                'description' => 'يمكن للأداة مساعدتك في تنظيم جميع أصول الوسائط وتخزينها وإدارتها في مكان واحد',
                'root'        => 'الجذر',
                'upload'      => 'تحميل',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'خصائص الأصل',
                        'create-btn' => 'إنشاء خاصية',

                        'datagrid' => [
                            'name'     => 'الاسم',
                            'type'     => 'النوع',
                            'language' => 'اللغة',
                            'value'    => 'القيمة',
                            'edit'     => 'تحرير',
                            'delete'   => 'حذف',
                        ],

                        'create' => [
                            'title'    => 'إنشاء خاصية',
                            'name'     => 'الاسم',
                            'type'     => 'النوع',
                            'language' => 'اللغة',
                            'value'    => 'القيمة',
                            'save-btn' => 'حفظ',
                        ],
                        'edit' => [
                            'title' => 'تحرير الخاصية',
                        ],
                        'delete-success' => 'تم حذف خاصية الأصل بنجاح',
                        'create-success' => 'تم إنشاء خاصية الأصل بنجاح',
                        'update-success' => 'تم تحديث خاصية الأصل بنجاح',
                    ],
                ],
                'comments' => [
                    'index'  => 'إضافة تعليق',
                    'create' => [
                        'create-success' => 'تمت إضافة التعليق بنجاح',
                    ],
                    'post-comment' => 'نشر تعليق',
                    'post-reply'   => 'نشر رد',
                    'reply'        => 'رد',
                    'add-reply'    => 'إضافة رد',
                    'add-comment'  => 'إضافة تعليق',
                    'no-comments'  => 'لا توجد تعليقات حتى الآن',

                ],
                'edit' => [
                    'title'              => 'تحرير الأصل',
                    'name'               => 'الاسم',
                    'value'              => 'القيمة',
                    'back-btn'           => 'رجوع',
                    'save-btn'           => 'حفظ',
                    'embedded_meta_info' => 'معلومات التعريف المضمنة',
                    'custom_meta_info'   => 'معلومات التعريف المخصصة',
                    'tags'               => 'العلامات',
                    'select-tags'        => 'اختر أو أنشئ علامة',
                    'tag'                => 'علامة',
                    'directory-path'     => 'مسار الدليل',
                    'add_tags'           => 'إضافة علامات',
                    'tab'                => [
                        'preview'          => 'معاينة',
                        'properties'       => 'الخصائص',
                        'comments'         => 'التعليقات',
                        'linked_resources' => 'المصادر المرتبطة',
                        'history'          => 'السجل',
                        'metadata'         => 'البيانات الوصفية',
                    ],
                    'button' => [
                        'download'        => 'تنزيل',
                        'custom_download' => 'تنزيل مخصص',
                        'rename'          => 'إعادة تسمية',
                        're_upload'       => 'إعادة تحميل',
                        'delete'          => 'حذف',
                    ],

                    'custom-download' => [
                        'title'              => 'تنزيل مخصص',
                        'format'             => 'التنسيق',
                        'width'              => 'العرض (بكسل)',
                        'width-placeholder'  => '200',
                        'height'             => 'الارتفاع (بكسل)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'تنزيل',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'أصلي',
                        ],
                    ],

                    'tag-already-exists'        => 'العلامة موجودة بالفعل',
                    'image-source-not-readable' => 'مصدر الصورة غير قابل للقراءة',
                    'failed-to-read'            => 'فشل قراءة بيانات تعريف الصورة :exception',
                    'file_re_upload_success'    => 'تمت إعادة تحميل الملفات بنجاح.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'المنتج',
                            'category'      => 'الفئة',
                            'product-sku'   => 'رمز المنتج: ',
                            'category code' => 'رمز الفئة: ',
                            'resource-type' => 'نوع المصدر',
                            'resource'      => 'المصدر',
                            'resource-view' => 'عرض المصدر',
                        ],
                    ],
                ],
                'delete-success'                          => 'تم حذف الأصل بنجاح',
                'delete-failed-due-to-attached-resources' => 'فشل حذف الأصل لأنه مرتبط بموارد (اسم الأصل: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'تم الحذف بنجاح.',
                    'files_upload_success' => 'تم تحميل الملفات بنجاح.',
                    'file_upload_success'  => 'تم تحميل الملف بنجاح.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'أصل',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'أصل',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'سد',
            'asset'            => 'أصل',
            'property'         => 'خاصية',
            'comment'          => 'تعليق',
            'linked_resources' => 'المصادر المرتبطة',
            'directory'        => 'دليل',
            'tag'              => 'علامة',
            'create'           => 'إنشاء',
            'edit'             => 'تحرير',
            'update'           => 'تحديث',
            'delete'           => 'حذف',
            'list'             => 'قائمة',
            'view'             => 'عرض',
            'upload'           => 'تحميل',
            're_upload'        => 'إعادة تحميل',
            'mass_update'      => 'تحديث جماعي',
            'mass_delete'      => 'حذف جماعي',
            'download'         => 'تنزيل',
            'custom_download'  => 'تنزيل مخصص',
            'rename'           => 'إعادة تسمية',
            'move'             => 'نقل',
            'copy'             => 'نسخ',
            'copy-structure'   => 'نسخ هيكل الدليل',
            'download-zip'     => 'تنزيل Zip',
            'asset-assign'     => 'تعيين أصل',
        ],

        'validation' => [
            'asset' => [
                'required' => 'حقل :attribute مطلوب.',
            ],

            'comment' => [
                'required' => 'رسالة التعليق مطلوبة.',
            ],

            'property' => [
                'name' => [
                    'required' => 'حقل الاسم مطلوب.',
                    'unique'   => 'الاسم مأخوذ بالفعل.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'هذا الإجراء غير مصرح به.',
        ],
    ],
];
