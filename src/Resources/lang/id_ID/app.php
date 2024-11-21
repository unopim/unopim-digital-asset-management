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
                    'message' => 'Menghapus direktori ini juga akan menghapus semua subdirektori di dalamnya. Tindakan ini bersifat permanen dan tidak dapat dibatalkan.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Tambah Media',
                    'assign-assets' => 'Tetapkan Media',
                    'assign'        => 'Tetapkan',
                    'preview-asset' => 'Pratinjau Media',
                    'preview'       => 'Pratinjau',
                    'remove'        => 'Hapus',
                    'download'      => 'Unduh',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Nama File',
                    'tags'           => 'Tag',
                    'property-name'  => 'Nama Properti',
                    'property-value' => 'Nilai Properti',
                    'created-at'     => 'Dibuat Pada',
                    'updated-at'     => 'Diperbarui Pada',
                    'extension'      => 'Ekstensi',
                    'path'           => 'Jalur',
                ],

                'directory' => [
                    'title'        => 'Direktori',
                    'create'       => [
                        'title'    => 'Buat Direktori',
                        'name'     => 'Nama',
                        'save-btn' => 'Simpan Direktori',
                    ],

                    'rename' => [
                        'title' => 'Ganti Nama Direktori',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Ganti Nama Media',
                            'save-btn' => 'Simpan Media',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Hapus',
                        'rename'                    => 'Ganti Nama',
                        'copy'                      => 'Salin',
                        'download'                  => 'Unduh',
                        'download-zip'              => 'Unduh ZIP',
                        'paste'                     => 'Tempel',
                        'add-directory'             => 'Tambah Direktori',
                        'upload-files'              => 'Unggah File',
                        'copy-directory-structured' => 'Salin Struktur Direktori',
                    ],

                    'not-found'                                 => 'Tidak ada direktori yang ditemukan',
                    'created-success'                           => 'Direktori berhasil dibuat',
                    'updated-success'                           => 'Direktori berhasil diperbarui',
                    'moved-success'                             => 'Direktori berhasil dipindahkan',
                    'can-not-deleted'                           => 'Direktori tidak dapat dihapus karena merupakan Direktori Root.',
                    'deleting-in-progress'                      => 'Penghapusan direktori sedang berlangsung',
                    'can-not-copy'                              => 'Direktori tidak dapat disalin karena merupakan Direktori Root.',
                    'coping-in-progress'                        => 'Penyalinan struktur direktori sedang berlangsung.',
                    'asset-not-found'                           => 'Tidak ada media yang ditemukan',
                    'asset-renamed-success'                     => 'Media berhasil diganti nama',
                    'asset-moved-success'                       => 'Media berhasil dipindahkan',
                    'asset-name-already-exist'                  => 'Nama baru sudah ada dengan media lain bernama :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Nama media konflik dengan file yang sudah ada di direktori yang sama.',
                    'old-file-not-found'                        => 'File yang diminta di jalur :old_path tidak ditemukan.',
                    'image-name-is-the-same'                    => 'Nama ini sudah ada. Silakan masukkan yang berbeda.',
                    'not-writable'                              => 'Anda tidak diizinkan untuk :actionType sebuah :type di lokasi ini ":path".',
                    'empty-directory'                           => 'Direktori ini kosong.',
                    'failed-download-directory'                 => 'Gagal membuat file ZIP.',
                ],

                'title'       => 'DAM',
                'description' => 'Alat ini dapat membantu Anda mengatur, menyimpan, dan mengelola semua media Anda di satu tempat',
                'root'        => 'Root',
                'upload'      => 'Unggah',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Properti Media',
                        'create-btn' => 'Buat Properti',

                        'datagrid'      => [
                            'name'     => 'Nama',
                            'type'     => 'Tipe',
                            'language' => 'Bahasa',
                            'value'    => 'Nilai',
                            'edit'     => 'Ubah',
                            'delete'   => 'Hapus',
                        ],

                        'create'     => [
                            'title'    => 'Buat Properti',
                            'name'     => 'Nama',
                            'type'     => 'Tipe',
                            'language' => 'Bahasa',
                            'value'    => 'Nilai',
                            'save-btn' => 'Simpan',
                        ],
                        'edit' => [
                            'title' => 'Ubah Properti',
                        ],
                        'delete-success' => 'Properti Media berhasil dihapus',
                        'create-success' => 'Properti Media berhasil dibuat',
                        'update-success' => 'Properti Media berhasil diperbarui',
                    ],
                ],
                'comments' => [
                    'index'  => 'Tambahkan Komentar',
                    'create' => [
                        'create-success' => 'Komentar berhasil ditambahkan',
                    ],
                    'post-comment' => 'Kirim Komentar',
                    'post-reply'   => 'Kirim Balasan',
                    'reply'        => 'Balas',
                    'add-reply'    => 'Tambah Balasan',
                    'add-comment'  => 'Tambah Komentar',
                    'no-comments'  => 'Belum Ada Komentar',

                ],
                'edit' => [
                    'title'              => 'Ubah Media',
                    'name'               => 'Nama',
                    'value'              => 'Nilai',
                    'back-btn'           => 'Kembali',
                    'save-btn'           => 'Simpan',
                    'embedded_meta_info' => 'Informasi Meta Tertanam',
                    'custom_meta_info'   => 'Informasi Meta Kustom',
                    'tags'               => 'Tag',
                    'select-tags'        => 'Pilih atau Buat Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Jalur Direktori',
                    'add_tags'           => 'Tambah Tag',
                    'tab'                => [
                        'preview'          => 'Pratinjau',
                        'properties'       => 'Properti',
                        'comments'         => 'Komentar',
                        'linked_resources' => 'Sumber Tertaut',
                        'history'          => 'Riwayat',
                    ],
                    'button' => [
                        'download'        => 'Unduh',
                        'custom_download' => 'Unduh Kustom',
                        'rename'          => 'Ganti Nama',
                        're_upload'       => 'Unggah Ulang',
                        'delete'          => 'Hapus',
                    ],

                    'custom-download' => [
                        'title'              => 'Unduh Kustom',
                        'format'             => 'Format',
                        'width'              => 'Lebar (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Tinggi (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Unduh',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Asli',
                        ],
                    ],

                    'tag-already-exists'        => 'Tag sudah ada',
                    'image-source-not-readable' => 'Sumber gambar tidak dapat dibaca',
                    'failed-to-read'            => 'Gagal membaca metadata gambar :exception',
                    'file_re_upload_success'    => 'File berhasil diunggah ulang.',
                ],

                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produk',
                            'category'      => 'Kategori',
                            'product-sku'   => 'SKU Produk: ',
                            'category code' => 'Kode Kategori: ',
                            'resource-type' => 'Jenis Sumber Daya',
                            'resource'      => 'Sumber Daya',
                            'resource-view' => 'Tampilan Sumber Daya',
                        ],
                    ],
                ],

                'delete-success'                          => 'Aset berhasil dihapus',
                'delete-failed-due-to-attached-resources' => 'Gagal menghapus aset karena tertaut ke sumber daya (Nama Aset: :assetNames)',

                'datagrid'                                => [
                    'mass-delete-success'  => 'Berhasil Dihapus Massal.',
                    'files_upload_success' => 'File Berhasil Diunggah.',
                    'file_upload_success'  => 'File Berhasil Diunggah.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Aset',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Aset',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Aset',
            'property'         => 'Properti',
            'comment'          => 'Komentar',
            'linked_resources' => 'Sumber Daya Tertaut',
            'directory'        => 'Direktori',
            'tag'              => 'Tag',
            'create'           => 'Buat',
            'edit'             => 'Sunting',
            'update'           => 'Perbarui',
            'delete'           => 'Hapus',
            'list'             => 'Daftar',
            'view'             => 'Lihat',
            'upload'           => 'Unggah',
            're_upload'        => 'Unggah Ulang',
            'mass_update'      => 'Pembaruan Massal',
            'mass_delete'      => 'Hapus Massal',
            'download'         => 'Unduh',
            'custom_download'  => 'Unduh Kustom',
            'rename'           => 'Ganti Nama',
            'move'             => 'Pindahkan',
            'copy'             => 'Salin',
            'copy-structure'   => 'Salin Struktur Direktori',
            'download-zip'     => 'Unduh Zip',
            'asset-assign'     => 'Tetapkan Aset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Kolom :attribute wajib diisi.',
            ],

            'comment' => [
                'required' => 'Pesan Komentar diperlukan.',
            ],

            'property' => [
                'name' => [
                    'required' => 'Kolom Nama wajib diisi.',
                    'unique'   => 'Nama telah diambil.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Tindakan ini tidak sah.',
        ],
    ],
];
