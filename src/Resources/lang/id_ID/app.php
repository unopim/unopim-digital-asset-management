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
                    'message' => 'Menghapus direktori ini juga akan menghapus semua subdirektori di dalamnya. Tindakan ini permanen dan tidak dapat dibatalkan.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Tambah Aset',
                    'assign-assets' => 'Tugaskan Aset',
                    'assign'        => 'Tugaskan',
                    'preview-asset' => 'Pratinjau Aset',
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
                    'path'           => 'Path',
                    'size'           => 'Ukuran',
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
                            'title'    => 'Ganti Nama Aset',
                            'save-btn' => 'Simpan Aset',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Hapus',
                        'rename'                    => 'Ganti Nama',
                        'copy'                      => 'Salin',
                        'download'                  => 'Unduh',
                        'download-zip'              => 'Unduh Zip',
                        'paste'                     => 'Tempel',
                        'add-directory'             => 'Tambah Direktori',
                        'upload-files'              => 'Unggah File',
                        'copy-directory-structured' => 'Salin Struktur Direktori',
                    ],

                    'not-found'                                 => 'Direktori tidak ditemukan',
                    'created-success'                           => 'Direktori berhasil dibuat',
                    'updated-success'                           => 'Direktori berhasil diperbarui',
                    'moved-success'                             => 'Direktori berhasil dipindahkan',
                    'fetch-all-success'                         => 'Direktori berhasil diambil',
                    'can-not-deleted'                           => 'Direktori tidak dapat dihapus karena merupakan Direktori Root.',
                    'deleting-in-progress'                      => 'Proses penghapusan direktori sedang berlangsung',
                    'can-not-copy'                              => 'Direktori tidak dapat disalin karena merupakan Direktori Root.',
                    'coping-in-progress'                        => 'Proses penyalinan struktur direktori sedang berlangsung.',
                    'asset-not-found'                           => 'Aset tidak ditemukan',
                    'asset-renamed-success'                     => 'Aset berhasil diganti namanya',
                    'asset-moved-success'                       => 'Aset berhasil dipindahkan',
                    'asset-name-already-exist'                  => 'Nama baru sudah ada dengan aset lain bernama :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Nama aset bertabrakan dengan file yang ada di direktori yang sama.',
                    'old-file-not-found'                        => 'File yang diminta pada path :old_path tidak ditemukan.',
                    'image-name-is-the-same'                    => 'Nama ini sudah ada. Harap masukkan nama yang berbeda.',
                    'not-writable'                              => 'Anda tidak diizinkan untuk :actionType :type di lokasi ini ":path".',
                    'empty-directory'                           => 'Direktori ini kosong.',
                    'failed-download-directory'                 => 'Gagal membuat file zip.',
                    'not-allowed'                               => 'Unggah file skrip tidak diperbolehkan.',
                ],

                'title'       => 'DAM',
                'description' => 'Alat ini dapat membantu Anda mengorganisir, menyimpan, dan mengelola semua aset media Anda di satu tempat',
                'root'        => 'Root',
                'upload'      => 'Unggah',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Properti Aset',
                        'create-btn' => 'Buat Properti',

                        'datagrid'      => [
                            'name'     => 'Nama',
                            'type'     => 'Tipe',
                            'language' => 'Bahasa',
                            'value'    => 'Nilai',
                            'edit'     => 'Edit',
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
                            'title' => 'Edit Properti',
                        ],
                        'delete-success' => 'Properti Aset Berhasil Dihapus',
                        'create-success' => 'Properti Aset Berhasil Dibuat',
                        'update-success' => 'Properti Aset Berhasil Diperbarui',
                        'not-found'      => 'Properti Tidak Ditemukan',
                        'found-success'  => 'Properti Berhasil Ditemukan',

                    ],
                ],
                'comments' => [
                    'index'  => 'Tambah Komentar',
                    'create' => [
                        'create-success' => 'Komentar berhasil ditambahkan',
                        'create-failure' => 'Komentar gagal dibuat',
                    ],
                    'post-comment'    => 'Kirim Komentar',
                    'post-reply'      => 'Kirim Balasan',
                    'reply'           => 'Balas',
                    'add-reply'       => 'Tambah Balasan',
                    'add-comment'     => 'Tambah Komentar',
                    'no-comments'     => 'Belum Ada Komentar',
                    'not-found'       => 'Komentar Tidak Ditemukan',
                    'updated-success' => 'Komentar berhasil diperbarui',
                    'update-failed'   => 'Komentar gagal diperbarui',
                    'delete-success'  => 'Komentar Aset Berhasil Dihapus',
                    'delete-failed'   => 'Komentar Aset gagal dihapus',
                ],
                'edit' => [
                    'title'              => 'Edit Aset',
                    'name'               => 'Nama',
                    'value'              => 'Nilai',
                    'back-btn'           => 'Kembali',
                    'save-btn'           => 'Simpan',
                    'embedded_meta_info' => 'Informasi Meta Tertanam',
                    'custom_meta_info'   => 'Informasi Meta Kustom',
                    'tags'               => 'Tag',
                    'select-tags'        => 'Pilih atau Buat Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Path Direktori',
                    'add_tags'           => 'Tambahkan Tag',
                    'tab'                => [
                        'preview'          => 'Pratinjau',
                        'properties'       => 'Properti',
                        'comments'         => 'Komentar',
                        'linked_resources' => 'Sumber Daya Terkait',
                        'history'          => 'Sejarah',
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
                    'file-re-upload-success'    => 'File berhasil diunggah ulang.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Produk',
                            'category'      => 'Kategori',
                            'product-sku'   => 'Produk SKU: ',
                            'category code' => 'Kode Kategori: ',
                            'resource-type' => 'Tipe Sumber Daya',
                            'resource'      => 'Sumber Daya',
                            'resource-view' => 'Tampilan Sumber Daya',
                        ],
                    ],
                    'found-success' => 'Sumber Daya Ditemukan Dengan Sukses',
                    'not-found'     => 'Sumber Daya Tidak Ditemukan',
                ],
                'tags' => [
                    'index'  => 'Tambah tag',
                    'create' => [
                        'create-success' => 'Tag berhasil ditambahkan',
                        'create-failure' => 'Tag gagal dibuat',
                    ],

                    'no-comments'    => 'Belum Ada Tag',
                    'found-success'  => 'Tag Ditemukan Dengan Sukses',
                    'not-found'      => 'Tag Tidak Ditemukan',
                    'update-success' => 'Tag berhasil diperbarui',
                    'update-failed'  => 'Tag gagal diperbarui',
                    'delete-success' => 'Tag aset berhasil dihapus',
                    'delete-failed'  => 'Tag Aset Gagal Dihapus',
                ],
                'delete-success'                          => 'Aset berhasil dihapus',
                'delete-failed-due-to-attached-resources' => 'Aset sedang digunakan. Lepaskan tautan sebelum menghapus',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Penghapusan Massal Berhasil.',
                    'files-upload-success'                => 'File Berhasil Diupload.',
                    'file-upload-success'                 => 'File Berhasil Diupload.',
                    'not-found'                           => 'File Tidak Ditemukan',
                    'edit-success'                        => 'File Berhasil Diedit',
                    'show-success'                        => 'File Berhasil Ditemukan',
                    'update-success'                      => 'File Berhasil Diperbarui',
                    'not-found-to-update'                 => 'File Tidak Ada',
                    'not-found-to-destroy'                => 'File Tidak Ada',
                    'files-upload-failed'                 => 'File gagal diunggah.',
                    'file-upload-failed'                  => 'File gagal diunggah',
                    'invalid-file'                        => 'File Tidak Valid',
                    'invalid-file-format'                 => 'Format Tidak Valid',
                    'invalid-file-format-or-not-provided' => 'Tidak ada file yang disediakan atau format tidak valid.',
                    'download-image-failed'               => 'Gagal mengunduh gambar dari URL',
                    'file-process-failed'                 => 'Beberapa file gagal diproses',
                    'file-forbidden-type'                 => 'File memiliki tipe atau ekstensi yang terlarang.',
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
            'linked_resources' => 'Sumber Daya Terkait',
            'directory'        => 'Direktori',
            'tag'              => 'Tag',
            'create'           => 'Buat',
            'edit'             => 'Edit',
            'update'           => 'Perbarui',
            'delete'           => 'Hapus',
            'list'             => 'Daftar',
            'view'             => 'Lihat',
            'upload'           => 'Unggah',
            're_upload'        => 'Unggah Ulang',
            'mass_update'      => 'Perbarui Massal',
            'mass_delete'      => 'Hapus Massal',
            'download'         => 'Unduh',
            'custom_download'  => 'Unduh Kustom',
            'rename'           => 'Ganti Nama',
            'move'             => 'Pindahkan',
            'copy'             => 'Salin',
            'copy-structure'   => 'Salin Struktur Direktori',
            'download-zip'     => 'Unduh Zip',
            'asset-assign'     => 'Tugaskan Aset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Kolom :attribute wajib diisi.',
            ],

            'comment' => [
                'required' => 'Pesan Komentar wajib diisi.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Kolom Tag wajib diisi.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Kolom Nama wajib diisi.',
                    'unique'   => 'Nama sudah digunakan.',
                ],
                'language' => [
                    'not-found' => 'Bahasa yang dipilih tidak ditemukan atau saat ini dinonaktifkan.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Aksi ini tidak sah.',
        ],
    ],
];
