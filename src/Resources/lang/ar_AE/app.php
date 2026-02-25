<?php

return [
    'admin' => [
        'components' => [
            'layouts' => [
                'sidebar' => [
                    'dam' => 'إدارة الأصول الرقمية',
                ],
            ],
            'modal' => [
                'confirm' => [
                    'message' => 'حذف هذا الدليل سيؤدي أيضًا إلى حذف جميع الأدلة الفرعية بداخله. هذه العملية دائمة ولا يمكن التراجع عنها.',
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
                'title' => 'إدارة الأصول الرقمية',

                'datagrid' => [
                    'file-name'      => 'اسم الملف',
                    'tags'           => 'العلامات',
                    'property-name'  => 'اسم الخاصية',
                    'property-value' => 'قيمة الخاصية',
                    'created-at'     => 'تم الإنشاء في',
                    'updated-at'     => 'تم التحديث في',
                    'extension'      => 'الامتداد',
                    'path'           => 'المسار',
                    'size'           => 'الحجم',
                ],

                'directory' => [
                    'title'        => 'الدليل',
                    'create'       => [
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
                        'download-zip'              => 'تنزيل كملف مضغوط',
                        'paste'                     => 'لصق',
                        'add-directory'             => 'إضافة دليل',
                        'upload-files'              => 'رفع الملفات',
                        'copy-directory-structured' => 'نسخ الدليل مع الهيكلية',
                    ],

                    'not-found'                                 => 'لم يتم العثور على دليل',
                    'created-success'                           => 'تم إنشاء الدليل بنجاح',
                    'updated-success'                           => 'تم تحديث الدليل بنجاح',
                    'moved-success'                             => 'تم نقل الدليل بنجاح',
                    'fetch-all-success'                         => 'تم جلب الأدلة بنجاح',
                    'can-not-deleted'                           => 'لا يمكن حذف الدليل لأنه دليل الجذر.',
                    'deleting-in-progress'                      => 'جارٍ حذف الدليل',
                    'can-not-copy'                              => 'لا يمكن نسخ الدليل لأنه دليل الجذر.',
                    'coping-in-progress'                        => 'جارٍ نسخ هيكل الدليل.',
                    'asset-not-found'                           => 'لم يتم العثور على أصل',
                    'asset-renamed-success'                     => 'تمت إعادة تسمية الأصل بنجاح',
                    'asset-moved-success'                       => 'تم نقل الأصل بنجاح',
                    'asset-name-already-exist'                  => 'الاسم الجديد موجود بالفعل مع أصل آخر باسم :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'اسم الأصل يتعارض مع ملف موجود في نفس الدليل.',
                    'old-file-not-found'                        => 'لم يتم العثور على الملف المطلوب في المسار :old_path.',
                    'image-name-is-the-same'                    => 'هذا الاسم موجود بالفعل. يرجى إدخال اسم مختلف.',
                    'not-writable'                              => 'أنت غير مسموح لك بـ :actionType :type في هذا الموقع ":path".',
                    'empty-directory'                           => 'هذا الدليل فارغ.',
                    'failed-download-directory'                 => 'فشل في إنشاء الملف المضغوط.',
                    'not-allowed'                               => 'رفع ملفات السكربت غير مسموح به.',
                ],

                'title'       => 'إدارة الأصول الرقمية',
                'description' => 'الأداة تساعدك على تنظيم وتخزين وإدارة جميع أصولك الرقمية في مكان واحد',
                'root'        => 'الجذر',
                'upload'      => 'رفع',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'خصائص الأصل',
                        'create-btn' => 'إنشاء خاصية',

                        'datagrid'      => [
                            'name'     => 'الاسم',
                            'type'     => 'النوع',
                            'language' => 'اللغة',
                            'value'    => 'القيمة',
                            'edit'     => 'تعديل',
                            'delete'   => 'حذف',
                        ],

                        'create'     => [
                            'title'    => 'إنشاء خاصية',
                            'name'     => 'الاسم',
                            'type'     => 'النوع',
                            'language' => 'اللغة',
                            'value'    => 'القيمة',
                            'save-btn' => 'حفظ',
                        ],
                        'edit' => [
                            'title' => 'تعديل الخاصية',
                        ],
                        'delete-success' => 'تم حذف خاصية الأصل بنجاح',
                        'create-success' => 'تم إنشاء خاصية الأصل بنجاح',
                        'update-success' => 'تم تحديث خاصية الأصل بنجاح',
                        'not-found'      => 'الخاصية غير موجودة',
                        'found-success'  => 'تم العثور على الخاصية بنجاح',

                    ],
                ],
                'comments' => [
                    'index'  => 'إضافة تعليق',
                    'create' => [
                        'create-success' => 'تم إضافة التعليق بنجاح',
                        'create-failure' => 'فشل في إنشاء التعليق',
                    ],
                    'post-comment'    => 'نشر تعليق',
                    'post-reply'      => 'نشر رد',
                    'reply'           => 'رد',
                    'add-reply'       => 'إضافة رد',
                    'add-comment'     => 'إضافة تعليق',
                    'no-comments'     => 'لا توجد تعليقات بعد',
                    'not-found'       => 'لم يتم العثور على تعليقات',
                    'updated-success' => 'تم تحديث التعليق بنجاح',
                    'update-failed'   => 'فشل في تحديث التعليق',
                    'delete-success'  => 'تم حذف تعليق الأصل بنجاح',
                    'delete-failed'   => 'فشل في حذف تعليق الأصل',
                ],
                'edit' => [
                    'title'              => 'تحرير الأصل',
                    'name'               => 'الاسم',
                    'value'              => 'القيمة',
                    'back-btn'           => 'رجوع',
                    'save-btn'           => 'حفظ',
                    'embedded_meta_info' => 'معلومات مدمجة',
                    'custom_meta_info'   => 'معلومات مخصصة',
                    'tags'               => 'العلامات',
                    'select-tags'        => 'اختر أو أنشئ علامة',
                    'tag'                => 'علامة',
                    'directory-path'     => 'مسار الدليل',
                    'add_tags'           => 'إضافة علامات',
                    'tab'                => [
                        'preview'          => 'معاينة',
                        'properties'       => 'الخصائص',
                        'comments'         => 'التعليقات',
                        'linked_resources' => 'الموارد المرتبطة',
                        'history'          => 'التاريخ',
                    ],
                    'button' => [
                        'download'        => 'تحميل',
                        'custom_download' => 'تحميل مخصص',
                        'rename'          => 'إعادة تسمية',
                        're_upload'       => 'إعادة تحميل',
                        'delete'          => 'حذف',
                    ],

                    'custom-download' => [
                        'title'              => 'تحميل مخصص',
                        'format'             => 'الصيغة',
                        'width'              => 'العرض (بكسل)',
                        'width-placeholder'  => '200',
                        'height'             => 'الارتفاع (بكسل)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'تحميل',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'أصلي',
                        ],
                    ],

                    'tag-already-exists'        => 'العلامة موجودة بالفعل',
                    'image-source-not-readable' => 'المصدر غير قابل للقراءة',
                    'failed-to-read'            => 'فشل في قراءة بيانات الصورة: :exception',
                    'file-re-upload-success'    => 'تمت إعادة تحميل الملفات بنجاح.',
                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'المنتج',
                            'category'      => 'الفئة',
                            'product-sku'   => 'رمز المنتج: ',
                            'category code' => 'رمز الفئة: ',
                            'resource-type' => 'نوع المورد',
                            'resource'      => 'المورد',
                            'resource-view' => 'عرض المورد',
                        ],
                    ],
                    'found-success' => 'تم العثور على المورد بنجاح',
                    'not-found'     => 'لم يتم العثور على المورد',
                ],
                'tags' => [
                    'index'  => 'إضافة علامات',
                    'create' => [
                        'create-success' => 'تمت إضافة العلامات بنجاح',
                        'create-failure' => 'فشل في إنشاء العلامات',
                    ],

                    'no-comments'    => 'لا توجد علامات بعد',
                    'found-success'  => 'تم العثور على العلامة بنجاح',
                    'not-found'      => 'لم يتم العثور على العلامات',
                    'update-success' => 'تم تحديث العلامات بنجاح',
                    'update-failed'  => 'فشل في تحديث العلامات',
                    'delete-success' => 'تم حذف وسوم الأصل بنجاح',
                    'delete-failed'  => 'فشل في حذف علامات الأصل',
                ],
                'delete-success'                          => 'تم حذف الأصل بنجاح',
                'delete-failed-due-to-attached-resources' => 'الأصل قيد الاستخدام. يرجى فصله قبل الحذف',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'تم الحذف الجماعي بنجاح.',
                    'files-upload-success'                => 'تم تحميل الملفات بنجاح.',
                    'file-upload-success'                 => 'تم تحميل الملف بنجاح.',
                    'not-found'                           => 'لم يتم العثور على الملف',
                    'edit-success'                        => 'تم تعديل الملف بنجاح',
                    'show-success'                        => 'تم العثور على الملف بنجاح',
                    'update-success'                      => 'تم تحديث الملف بنجاح',
                    'not-found-to-update'                 => 'الملف غير موجود',
                    'not-found-to-destroy'                => 'الملف غير موجود',
                    'files-upload-failed'                 => 'فشل في تحميل الملفات.',
                    'file-upload-failed'                  => 'فشل في تحميل الملف',
                    'invalid-file'                        => 'تم تقديم ملف غير صالح',
                    'invalid-file-format'                 => 'صيغة غير صالحة',
                    'invalid-file-format-or-not-provided' => 'لم يتم تقديم ملفات أو صيغة غير صالحة.',
                    'download-image-failed'               => 'فشل في تحميل الصورة من الرابط',
                    'file-process-failed'                 => 'فشل في معالجة بعض الملفات',
                    'file-forbidden-type'                 => 'الملف يحتوي على نوع أو امتداد محظور.',
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
            'menu'             => 'إدارة الأصول',
            'asset'            => 'أصل',
            'property'         => 'خاصية',
            'comment'          => 'تعليق',
            'linked_resources' => 'الموارد المرتبطة',
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
            'download'         => 'تحميل',
            'custom_download'  => 'تحميل مخصص',
            'rename'           => 'إعادة تسمية',
            'move'             => 'نقل',
            'copy'             => 'نسخ',
            'copy-structure'   => 'نسخ هيكل الدليل',
            'download-zip'     => 'تحميل كملف مضغوط',
            'asset-assign'     => 'تعيين الأصل',
        ],

        'validation' => [
            'asset' => [
                'required' => 'حقل :attribute مطلوب.',
            ],

            'comment' => [
                'required' => 'رسالة التعليق مطلوبة.',
            ],

            'tag' => [
                'name' => [
                    'required' => 'حقل العلامة مطلوب.',
                ],
            ],

            'property' => [
                'name' => [
                    'required' => 'حقل الاسم مطلوب.',
                    'unique'   => 'تم استخدام الاسم مسبقًا.',
                ],
                'language' => [
                    'not-found' => 'اللغة المحددة غير موجودة أو غير مفعّلة حالياً.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'هذا الإجراء غير مصرح به.',
        ],
    ],
];
