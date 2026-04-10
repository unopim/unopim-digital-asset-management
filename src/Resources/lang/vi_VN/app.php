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
                    'message' => 'Việc xóa thư mục này cũng sẽ xóa tất cả các thư mục con bên trong. Hành động này là vĩnh viễn và không thể hoàn tác.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Thêm tài sản',
                    'assign-assets' => 'Gán tài sản',
                    'assign'        => 'Gán',
                    'preview-asset' => 'Xem trước tài sản',
                    'preview'       => 'Xem trước',
                    'remove'        => 'Xóa',
                    'download'      => 'Tải xuống',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'Tên tệp',
                    'tags'           => 'Thẻ',
                    'property-name'  => 'Tên thuộc tính',
                    'property-value' => 'Giá trị thuộc tính',
                    'created-at'     => 'Đã tạo lúc',
                    'updated-at'     => 'Đã cập nhật lúc',
                    'extension'      => 'Phần mở rộng',
                    'path'           => 'Đường dẫn',
                    'size'           => 'Kích thước',
                ],

                'directory' => [
                    'title'        => 'Thư mục',
                    'create'       => [
                        'title'    => 'Tạo thư mục',
                        'name'     => 'Tên',
                        'save-btn' => 'Lưu thư mục',
                    ],

                    'rename' => [
                        'title' => 'Đổi tên thư mục',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Đổi tên tài sản',
                            'save-btn' => 'Lưu tài sản',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Xóa',
                        'rename'                    => 'Đổi tên',
                        'copy'                      => 'Sao chép',
                        'download'                  => 'Tải xuống',
                        'download-zip'              => 'Tải xuống Zip',
                        'paste'                     => 'Dán',
                        'add-directory'             => 'Thêm thư mục',
                        'upload-files'              => 'Tải lên tệp',
                        'copy-directory-structured' => 'Sao chép cấu trúc thư mục',
                        'get-by-id'                 => 'Lấy theo ID',
                        'comment'                   => 'Bình luận',
                    ],

                    'linked-resources'                          => 'Tài nguyên liên kết',
                    'not-found'                                 => 'Không tìm thấy thư mục',
                    'created-success'                           => 'Thư mục đã được tạo thành công',
                    'updated-success'                           => 'Thư mục đã được cập nhật thành công',
                    'moved-success'                             => 'Thư mục đã được di chuyển thành công',
                    'fetch-all-success'                         => 'Đã lấy danh sách thư mục thành công',
                    'can-not-deleted'                           => 'Không thể xóa vì đây là thư mục gốc.',
                    'deleting-in-progress'                      => 'Đang tiến hành xóa thư mục',
                    'can-not-copy'                              => 'Không thể sao chép vì đây là thư mục gốc.',
                    'coping-in-progress'                        => 'Đang tiến hành sao chép cấu trúc thư mục.',
                    'asset-not-found'                           => 'Không tìm thấy tài sản',
                    'asset-renamed-success'                     => 'Đã đổi tên tài sản thành công',
                    'asset-moved-success'                       => 'Đã di chuyển tài sản thành công',
                    'asset-name-already-exist'                  => 'Tên mới đã tồn tại với một tài sản khác có tên :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'Tên tài sản xung đột với một tệp hiện có trong cùng thư mục.',
                    'old-file-not-found'                        => 'Không tìm thấy tệp được yêu cầu tại đường dẫn :old_path.',
                    'image-name-is-the-same'                    => 'Tên này đã tồn tại. Vui lòng nhập một tên khác.',
                    'not-writable'                              => 'Bạn không được phép :actionType một :type tại vị trí này ":path".',
                    'empty-directory'                           => 'Thư mục này trống.',
                    'failed-download-directory'                 => 'Không thể tạo tệp zip.',
                    'not-allowed'                               => 'Không cho phép tải lên các tệp script.',
                ],

                'title'       => 'DAM',
                'description' => 'Công cụ giúp bạn tổ chức, lưu trữ và quản lý tất cả tài sản phương tiện ở một nơi',
                'root'        => 'Gốc',
                'upload'      => 'Tải lên',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Thuộc tính tài sản',
                        'create-btn' => 'Tạo thuộc tính',

                        'datagrid'      => [
                            'name'     => 'Tên',
                            'type'     => 'Loại',
                            'language' => 'Ngôn ngữ',
                            'value'    => 'Giá trị',
                            'edit'     => 'Chỉnh sửa',
                            'delete'   => 'Xóa',
                        ],

                        'create'     => [
                            'title'    => 'Tạo thuộc tính',
                            'name'     => 'Tên',
                            'type'     => 'Loại',
                            'language' => 'Ngôn ngữ',
                            'value'    => 'Giá trị',
                            'save-btn' => 'Lưu',
                        ],
                        'edit' => [
                            'title' => 'Chỉnh sửa thuộc tính',
                        ],
                        'delete-success' => 'Đã xóa thuộc tính tài sản thành công',
                        'create-success' => 'Đã tạo thuộc tính tài sản thành công',
                        'update-success' => 'Đã cập nhật thuộc tính tài sản thành công',
                        'not-found'      => 'Không tìm thấy thuộc tính',
                        'found-success'  => 'Đã tìm thấy thuộc tính thành công',
                    ],
                ],
                'comments' => [
                    'index'  => 'Thêm bình luận',
                    'create' => [
                        'create-success' => 'Bình luận đã được thêm thành công',
                        'create-failure' => 'Không thể tạo bình luận',
                    ],
                    'post-comment'    => 'Đăng bình luận',
                    'post-reply'      => 'Đăng trả lời',
                    'reply'           => 'Trả lời',
                    'add-reply'       => 'Thêm trả lời',
                    'add-comment'     => 'Thêm bình luận',
                    'no-comments'     => 'Chưa có bình luận nào',
                    'not-found'       => 'Không tìm thấy bình luận',
                    'updated-success' => 'Đã cập nhật bình luận thành công',
                    'update-failed'   => 'Không thể cập nhật bình luận',
                    'delete-success'  => 'Đã xóa bình luận tài sản thành công',
                    'delete-failed'   => 'Không thể xóa bình luận tài sản',
                ],
                'edit' => [
                    'title'              => 'Chỉnh sửa tài sản',
                    'name'               => 'Tên',
                    'value'              => 'Giá trị',
                    'back-btn'           => 'Quay lại',
                    'save-btn'           => 'Lưu',
                    'embedded_meta_info' => 'Thông tin meta nhúng',
                    'custom_meta_info'   => 'Thông tin meta tùy chỉnh',
                    'tags'               => 'Thẻ',
                    'select-tags'        => 'Chọn hoặc tạo một thẻ',
                    'tag'                => 'Thẻ',
                    'directory-path'     => 'Đường dẫn thư mục',
                    'add_tags'           => 'Thêm thẻ',
                    'tab'                => [
                        'preview'          => 'Xem trước',
                        'properties'       => 'Thuộc tính',
                        'comments'         => 'Bình luận',
                        'linked_resources' => 'Tài nguyên liên kết',
                        'history'          => 'Lịch sử',
                    ],
                    'button' => [
                        'download'        => 'Tải xuống',
                        'custom_download' => 'Tải xuống tùy chỉnh',
                        'rename'          => 'Đổi tên',
                        're_upload'       => 'Tải lên lại',
                        'delete'          => 'Xóa',
                    ],

                    'custom-download' => [
                        'title'              => 'Tải xuống tùy chỉnh',
                        'format'             => 'Định dạng',
                        'width'              => 'Chiều rộng (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Chiều cao (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Tải xuống',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Gốc',
                        ],
                    ],

                    'tag-already-exists'        => 'Thẻ đã tồn tại',
                    'image-source-not-readable' => 'Không thể đọc nguồn hình ảnh',
                    'failed-to-read'            => 'Không thể đọc siêu dữ liệu hình ảnh :exception',
                    'file-re-upload-success'    => 'Tệp đã được tải lên lại thành công.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Sản phẩm',
                            'category'      => 'Danh mục',
                            'product-sku'   => 'Sku sản phẩm: ',
                            'category code' => 'Mã danh mục: ',
                            'resource-type' => 'Loại tài nguyên',
                            'resource'      => 'Tài nguyên',
                            'resource-view' => 'Xem tài nguyên',
                        ],
                    ],
                    'found-success' => 'Đã tìm thấy tài nguyên thành công',
                    'not-found'     => 'Không tìm thấy tài nguyên',
                ],
                'tags' => [
                    'index'  => 'Thêm thẻ',
                    'create' => [
                        'create-success' => 'Thẻ đã được thêm thành công',
                        'create-failure' => 'Không thể tạo thẻ',
                    ],

                    'no-comments'    => 'Chưa có thẻ nào',
                    'found-success'  => 'Đã tìm thấy thẻ thành công',
                    'not-found'      => 'Không tìm thấy thẻ',
                    'update-success' => 'Đã cập nhật thẻ thành công',
                    'update-failed'  => 'Không thể cập nhật thẻ',
                    'delete-success' => 'Đã xóa thẻ tài sản thành công',
                    'delete-failed'  => 'Không thể xóa thẻ tài sản',
                ],
                'delete-success'                          => 'Đã xóa tài sản thành công',
                'delete-failed-due-to-attached-resources' => 'Tài sản đang được sử dụng. Hủy liên kết trước khi xóa',
                'datagrid'                                => [
                    'mass-delete-success'                 => 'Đã xóa hàng loạt thành công.',
                    'files-upload-success'                => 'Đã tải lên tệp thành công.',
                    'file-upload-success'                 => 'Đã tải lên tệp thành công.',
                    'not-found'                           => 'Không tìm thấy tệp',
                    'edit-success'                        => 'Đã tải lên tệp thành công',
                    'show-success'                        => 'Đã tìm thấy tệp thành công',
                    'update-success'                      => 'Đã cập nhật tệp thành công',
                    'not-found-to-update'                 => 'Tệp không tồn tại',
                    'not-found-to-destroy'                => 'Tệp không tồn tại',
                    'files-upload-failed'                 => 'Không thể tải lên tệp.',
                    'file-upload-failed'                  => 'Không thể tải lên tệp',
                    'invalid-file'                        => 'Tệp được cung cấp không hợp lệ',
                    'invalid-file-format'                 => 'Định dạng không hợp lệ',
                    'invalid-file-format-or-not-provided' => 'Không có tệp nào được cung cấp hoặc định dạng không hợp lệ.',
                    'download-image-failed'               => 'Không thể tải xuống hình ảnh từ URL',
                    'file-process-failed'                 => 'Một số tệp xử lý không thành công',
                    'file-forbidden-type'                 => 'Tệp có loại hoặc phần mở rộng bị cấm.',
                    'file-too-large'                      => 'Tệp quá lớn. Kích thước tối đa cho phép là :size.',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Tài sản',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Tài sản',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Tài sản',
            'property'         => 'Thuộc tính',
            'comment'          => 'Bình luận',
            'linked_resources' => 'Tài nguyên liên kết',
            'directory'        => 'Thư mục',
            'tag'              => 'Thẻ',
            'create'           => 'Tạo',
            'edit'             => 'Chỉnh sửa',
            'update'           => 'Cập nhật',
            'delete'           => 'Xóa',
            'list'             => 'Danh sách',
            'view'             => 'Xem',
            'upload'           => 'Tải lên',
            're_upload'        => 'Tải lên lại',
            'mass_update'      => 'Cập nhật hàng loạt',
            'mass_delete'      => 'Xóa hàng loạt',
            'download'         => 'Tải xuống',
            'custom_download'  => 'Tải xuống tùy chỉnh',
            'rename'           => 'Đổi tên',
            'move'             => 'Di chuyển',
            'copy'             => 'Sao chép',
            'copy-structure'   => 'Sao chép cấu trúc thư mục',
            'download-zip'     => 'Tải xuống Zip',
            'asset-assign'     => 'Gán tài sản',
        ],

        'validation' => [
            'asset' => [
                'required' => 'Trường :attribute là bắt buộc.',
            ],

            'comment' => [
                'required' => 'Nội dung bình luận là bắt buộc.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'Trường Thẻ là bắt buộc.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'Trường Tên là bắt buộc.',
                    'unique'   => 'Tên đã được sử dụng.',
                ],
                'language' => [
                    'not-found' => 'Không tìm thấy ngôn ngữ đã chọn hoặc hiện đang bị vô hiệu hóa.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'Hành động này không được phép.',
        ],
    ],
];
