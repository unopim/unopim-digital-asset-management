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
                    'message' => 'Bu dizini silmek, içindeki tüm alt dizinleri de silecektir. Bu işlem kalıcıdır ve geri alınamaz.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Varlık ekle',
                    'assign-assets' => 'Varlıkları ata',
                    'assign'        => 'Ata',
                    'preview-asset' => 'Varlık önizlemesi',
                    'preview'       => 'Önizleme',
                    'remove'        => 'Kaldır',
                    'download'      => 'İndir',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Dosya adı',
                    'tags'           => 'Etiketler',
                    'property-name'  => 'Özellik adı',
                    'property-value' => 'Özellik değeri',
                    'created-at'     => 'Oluşturulma tarihi',
                    'updated-at'     => 'Güncellenme tarihi',
                    'extension'      => 'Uzantı',
                    'path'           => 'Yol',
                    'size'           => 'Boyut',
                ],

                'directory' => [
                    'title'  => 'Dizin',
                    'create' => [
                        'title'    => 'Dizin oluştur',
                        'name'     => 'Ad',
                        'save-btn' => 'Dizini kaydet',
                    ],

                    'rename' => [
                        'title' => 'Dizini yeniden adlandır',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Varlığı yeniden adlandır',
                            'save-btn' => 'Varlığı kaydet',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Sil',
                        'rename'                    => 'Yeniden adlandır',
                        'copy'                      => 'Kopyala',
                        'download'                  => 'İndir',
                        'download-zip'              => 'Zip olarak indir',
                        'paste'                     => 'Yapıştır',
                        'add-directory'             => 'Dizin ekle',
                        'upload-files'              => 'Dosya yükle',
                        'copy-directory-structured' => 'Dizin yapısını kopyala',
                        'get-by-id'                 => 'Kimliğe göre al',
                        'comment'                   => 'Yorum',
                    ],

                    'linked-resources'                          => 'Bağlı kaynaklar',
                    'not-found'                                 => 'Dizin bulunamadı',
                    'created-success'                           => 'Dizin başarıyla oluşturuldu',
                    'updated-success'                           => 'Dizin başarıyla güncellendi',
                    'moved-success'                             => 'Dizin başarıyla taşındı',
                    'fetch-all-success'                         => 'Dizinler başarıyla alındı',
                    'can-not-deleted'                           => 'Dizin kök dizin olduğu için silinemez.',
                    'deleting-in-progress'                      => 'Dizin silme işlemi devam ediyor',
                    'can-not-copy'                              => 'Dizin kök dizin olduğu için kopyalanamaz.',
                    'coping-in-progress'                        => 'Dizin yapısının kopyalanması devam ediyor.',
                    'asset-not-found'                           => 'Varlık bulunamadı',
                    'asset-renamed-success'                     => 'Varlık başarıyla yeniden adlandırıldı',
                    'asset-moved-success'                       => 'Varlık başarıyla taşındı',
                    'asset-name-already-exist'                  => 'Yeni ad, :asset_name adlı başka bir varlıkta zaten mevcut',
                    'asset-name-conflict-in-the-same-directory' => 'Varlık adı, aynı dizindeki mevcut bir dosya ile çakışıyor.',
                    'old-file-not-found'                        => ':old_path yolundaki dosya bulunamadı.',
                    'image-name-is-the-same'                    => 'Bu ad zaten mevcut. Lütfen farklı bir ad girin.',
                    'not-writable'                              => 'Bu konumda ":path" bir :type üzerinde :actionType işlemine izniniz yok.',
                    'empty-directory'                           => 'Bu dizin boş.',
                    'failed-download-directory'                 => 'Zip dosyası oluşturulamadı.',
                    'not-allowed'                               => 'Betik dosyaları yüklemeye izin verilmiyor.',
                ],

                'title'       => 'DAM',
                'description' => 'Tüm medya varlıklarınızı tek bir yerde düzenlemenize, saklamanıza ve yönetmenize yardımcı olan araç',
                'root'        => 'Kök',
                'upload'      => 'Yükle',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Varlık özellikleri',
                        'create-btn' => 'Özellik oluştur',

                        'datagrid' => [
                            'name'     => 'Ad',
                            'type'     => 'Tür',
                            'language' => 'Dil',
                            'value'    => 'Değer',
                            'edit'     => 'Düzenle',
                            'delete'   => 'Sil',
                        ],

                        'create' => [
                            'title'    => 'Özellik oluştur',
                            'name'     => 'Ad',
                            'type'     => 'Tür',
                            'language' => 'Dil',
                            'value'    => 'Değer',
                            'save-btn' => 'Kaydet',
                        ],
                        'edit' => [
                            'title' => 'Özelliği düzenle',
                        ],
                        'delete-success' => 'Varlık özelliği başarıyla silindi',
                        'create-success' => 'Varlık özelliği başarıyla oluşturuldu',
                        'update-success' => 'Varlık özelliği başarıyla güncellendi',
                        'not-found'      => 'Özellik bulunamadı',
                        'found-success'  => 'Özellik başarıyla bulundu',
                    ],
                ],
                'comments' => [
                    'index'  => 'Yorum ekle',
                    'create' => [
                        'create-success' => 'Yorum başarıyla eklendi',
                        'create-failure' => 'Yorum oluşturulamadı',
                    ],
                    'post-comment'    => 'Yorumu gönder',
                    'post-reply'      => 'Yanıtı gönder',
                    'reply'           => 'Yanıtla',
                    'add-reply'       => 'Yanıt ekle',
                    'add-comment'     => 'Yorum ekle',
                    'no-comments'     => 'Henüz yorum yok',
                    'not-found'       => 'Yorumlar bulunamadı',
                    'updated-success' => 'Yorum başarıyla güncellendi',
                    'update-failed'   => 'Yorum güncellenemedi',
                    'delete-success'  => 'Varlık yorumu başarıyla silindi',
                    'delete-failed'   => 'Varlık yorumu silinemedi',
                ],
                'edit' => [
                    'title'              => 'Varlığı düzenle',
                    'name'               => 'Ad',
                    'value'              => 'Değer',
                    'back-btn'           => 'Geri',
                    'save-btn'           => 'Kaydet',
                    'embedded_meta_info' => 'Gömülü meta bilgisi',
                    'custom_meta_info'   => 'Özel meta bilgisi',
                    'tags'               => 'Etiketler',
                    'select-tags'        => 'Bir etiket seçin veya oluşturun',
                    'tag'                => 'Etiket',
                    'directory-path'     => 'Dizin yolu',
                    'add_tags'           => 'Etiket ekle',
                    'tab'                => [
                        'preview'          => 'Önizleme',
                        'properties'       => 'Özellikler',
                        'comments'         => 'Yorumlar',
                        'linked_resources' => 'Bağlı kaynaklar',
                        'history'          => 'Geçmiş',
                    ],
                    'button' => [
                        'download'        => 'İndir',
                        'custom_download' => 'Özel indirme',
                        'rename'          => 'Yeniden adlandır',
                        're_upload'       => 'Yeniden yükle',
                        'delete'          => 'Sil',
                    ],

                    'custom-download' => [
                        'title'              => 'Özel indirme',
                        'format'             => 'Biçim',
                        'width'              => 'Genişlik (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Yükseklik (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'İndir',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Orijinal',
                        ],
                    ],

                    'tag-already-exists'        => 'Etiket zaten mevcut',
                    'image-source-not-readable' => 'Görüntü kaynağı okunamıyor',
                    'failed-to-read'            => 'Görüntü meta verileri okunamadı :exception',
                    'file-re-upload-success'    => 'Dosyalar başarıyla yeniden yüklendi.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Ürün',
                            'category'      => 'Kategori',
                            'product-sku'   => 'Ürün Sku: ',
                            'category code' => 'Kategori kodu: ',
                            'resource-type' => 'Kaynak türü',
                            'resource'      => 'Kaynak',
                            'resource-view' => 'Kaynak görünümü',
                        ],
                    ],
                    'found-success' => 'Kaynak başarıyla bulundu',
                    'not-found'     => 'Kaynak bulunamadı',
                ],
                'tags' => [
                    'index'  => 'Etiket ekle',
                    'create' => [
                        'create-success' => 'Etiketler başarıyla eklendi',
                        'create-failure' => 'Etiketler oluşturulamadı',
                    ],

                    'no-comments'    => 'Henüz etiket yok',
                    'found-success'  => 'Etiket başarıyla bulundu',
                    'not-found'      => 'Etiketler bulunamadı',
                    'update-success' => 'Etiketler başarıyla güncellendi',
                    'update-failed'  => 'Etiketler güncellenemedi',
                    'delete-success' => 'Varlık etiketleri başarıyla kaldırıldı',
                    'delete-failed'  => 'Varlık etiketleri silinemedi',
                ],
                'delete-success'                          => 'Varlık başarıyla silindi',
                'delete-failed-due-to-attached-resources' => 'Varlık kullanımda. Silmeden önce bağlantıyı kaldırın',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Toplu silme başarıyla tamamlandı.',
                    'files-upload-success'                => 'Dosyalar başarıyla yüklendi.',
                    'file-upload-success'                 => 'Dosya başarıyla yüklendi.',
                    'not-found'                           => 'Dosya bulunamadı',
                    'edit-success'                        => 'Dosya başarıyla yüklendi',
                    'show-success'                        => 'Dosya başarıyla bulundu',
                    'update-success'                      => 'Dosya başarıyla güncellendi',
                    'not-found-to-update'                 => 'Dosya mevcut değil',
                    'not-found-to-destroy'                => 'Dosya mevcut değil',
                    'files-upload-failed'                 => 'Dosyalar yüklenemedi.',
                    'file-upload-failed'                  => 'Dosya yüklenemedi',
                    'invalid-file'                        => 'Geçersiz dosya sağlandı',
                    'invalid-file-format'                 => 'Geçersiz biçim',
                    'invalid-file-format-or-not-provided' => 'Dosya sağlanmadı veya geçersiz biçim.',
                    'download-image-failed'               => 'Görüntü URL\'den indirilemedi',
                    'file-process-failed'                 => 'Bazı dosyalar işlenemedi',
                    'file-forbidden-type'                 => 'Dosyanın türü veya uzantısı yasaklı.',
                    'file-too-large'                      => 'Dosya çok büyük. İzin verilen maksimum boyut: :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Varlık',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Varlık',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Varlık',
            'property'         => 'Özellik',
            'comment'          => 'Yorum',
            'linked_resources' => 'Bağlı kaynaklar',
            'directory'        => 'Dizin',
            'tag'              => 'Etiket',
            'create'           => 'Oluştur',
            'edit'             => 'Düzenle',
            'update'           => 'Güncelle',
            'delete'           => 'Sil',
            'list'             => 'Liste',
            'view'             => 'Görüntüle',
            'upload'           => 'Yükle',
            're_upload'        => 'Yeniden yükle',
            'mass_update'      => 'Toplu güncelleme',
            'mass_delete'      => 'Toplu silme',
            'download'         => 'İndir',
            'custom_download'  => 'Özel indirme',
            'rename'           => 'Yeniden adlandır',
            'move'             => 'Taşı',
            'copy'             => 'Kopyala',
            'copy-structure'   => 'Dizin yapısını kopyala',
            'download-zip'     => 'Zip olarak indir',
            'asset-assign'     => 'Varlık ata',
        ],

        'validation' => [
            'asset' => [
                'required' => ':attribute alanı zorunludur.',
            ],

            'comment' => [
                'required' => 'Yorum mesajı zorunludur.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Etiket alanı zorunludur.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Ad alanı zorunludur.',
                    'unique'   => 'Bu ad zaten alınmış.',
                ],
                'language' => [
                    'not-found' => 'Seçilen dil bulunamadı veya şu anda devre dışı.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Bu işlem için yetkiniz yok.',
        ],
    ],
];
