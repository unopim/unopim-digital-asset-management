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
                    'message' => 'Deleting this directory will also delete all subdirectories inside it. This action is permanent and cannot be undone.',
                ],
            ],
            'asset' => [
                'field' => [
                    'add-asset'     => 'Add Asset',
                    'assign-assets' => 'Assign Assets',
                    'assign'        => 'Assign',
                    'preview-asset' => 'Preview Asset',
                    'preview'       => 'Preview',
                    'remove'        => 'Remove',
                    'download'      => 'Download',
                ],
            ],
        ],
        'dam' => [
            'index' => [
                'title' => 'DAM',

                'datagrid' => [
                    'file-name'      => 'File Name',
                    'tags'           => 'Tags',
                    'property-name'  => 'Property Name',
                    'property-value' => 'Property Value',
                    'created-at'     => 'Created At',
                    'updated-at'     => 'Updated At',
                    'extension'      => 'Extension',
                    'path'           => 'Path',
                ],

                'directory' => [
                    'title'        => 'Directory',
                    'create'       => [
                        'title'    => 'Create Directory',
                        'name'     => 'Name',
                        'save-btn' => 'Save Directory',
                    ],

                    'rename' => [
                        'title' => 'Rename Directory',
                    ],

                    'asset' => [
                        'rename' => [
                            'title'    => 'Rename Asset',
                            'save-btn' => 'Save Asset',
                        ],
                    ],

                    'actions' => [
                        'delete'                    => 'Delete',
                        'rename'                    => 'Rename',
                        'copy'                      => 'Copy',
                        'download'                  => 'Download',
                        'download-zip'              => 'Download Zip',
                        'paste'                     => 'Paste',
                        'add-directory'             => 'Add Directory',
                        'upload-files'              => 'Upload Files',
                        'copy-directory-structured' => 'Copy Directory Structured',
                    ],

                    'not-found'                                 => 'No directory found',
                    'created-success'                           => 'Directory created successfully',
                    'updated-success'                           => 'Directory updated successfully',
                    'moved-success'                             => 'Directory moved successfully',
                    'fetch-all-success'                         => 'Directories fetched successfully',
                    'can-not-deleted'                           => 'Directory cannot be deleted as it is Root Directory.',
                    'deleting-in-progress'                      => 'Directory deleting in-progress',
                    'can-not-copy'                              => 'Directory cannot be copy as it is Root Directory.',
                    'coping-in-progress'                        => 'Directory structure coping in-progress.',
                    'asset-not-found'                           => 'No asset found',
                    'asset-renamed-success'                     => 'Asset renamed successfully',
                    'asset-moved-success'                       => 'Asset moved successfully',
                    'asset-name-already-exist'                  => 'The new name already exists with another asset named :asset_name',
                    'asset-name-conflict-in-the-same-directory' => 'The asset name conflicts with an existing file in the same directory.',
                    'old-file-not-found'                        => 'The file requested at the path :old_path was not found.',
                    'image-name-is-the-same'                    => 'This name is already exist. Please enter a different one.',
                    'not-writable'                              => 'You are not allowed to :actionType a :type in this location ":path".',
                    'empty-directory'                           => 'This directory is empty.',
                    'failed-download-directory'                 => 'Failed to create the zip file.',
                ],

                'title'       => 'DAM',
                'description' => 'Tool can help you organise, store, and manage all your media asset in one place',
                'root'        => 'Root',
                'upload'      => 'Upload',
            ],
            'asset' => [
                'properties' => [
                    'index' => [
                        'title'      => 'Asset Properties',
                        'create-btn' => 'Create Property',

                        'datagrid'      => [
                            'name'     => 'Name',
                            'type'     => 'Type',
                            'language' => 'Language',
                            'value'    => 'Value',
                            'edit'     => 'Edit',
                            'delete'   => 'Delete',
                        ],

                        'create'     => [
                            'title'    => 'Create Property',
                            'name'     => 'Name',
                            'type'     => 'Type',
                            'language' => 'Language',
                            'value'    => 'Value',
                            'save-btn' => 'Save',
                        ],
                        'edit' => [
                            'title' => 'Edit Property',
                        ],
                        'delete-success' => 'Asset Property Deleted Successfully',
                        'create-success' => 'Asset Property Created Successfully',
                        'update-success' => 'Asset Property Updated Successfully',
                        'not-found'      => 'Property Not Found',
                        'found-success'  => 'Property Found Successfully',
                    ],
                ],
                'comments' => [
                    'index'  => 'Add Comment',
                    'create' => [
                        'create-success' => 'Comment has been successfully added',
                        'create-failure' => 'Comment failed to create',
                    ],
                    'post-comment'    => 'Post Comment',
                    'post-reply'      => 'Post Reply',
                    'reply'           => 'Reply',
                    'add-reply'       => 'Add Reply',
                    'add-comment'     => 'Add Comment',
                    'no-comments'     => 'No Comments Yet',
                    'not-found'       => 'Comments Not Found',
                    'updated-success' => 'Comment updated Successfully',
                    'update-failed'   => 'Comment failed to update',
                    'delete-success'  => 'Asset Comment Deleted Successfully',
                    'delete-failed'   => 'Asset Comment failed to delete',

                ],
                'edit' => [
                    'title'              => 'Edit Asset',
                    'name'               => 'Name',
                    'value'              => 'Value',
                    'back-btn'           => 'Back',
                    'save-btn'           => 'Save',
                    'embedded_meta_info' => 'Embedded Meta Info',
                    'custom_meta_info'   => 'Custom Meta Info',
                    'tags'               => 'Tags',
                    'select-tags'        => 'Choose or Create a Tag',
                    'tag'                => 'Tag',
                    'directory-path'     => 'Directory Path',
                    'add_tags'           => 'Add Tags',
                    'tab'                => [
                        'preview'          => 'Preview',
                        'properties'       => 'Properties',
                        'comments'         => 'Comments',
                        'linked_resources' => 'Linked Resources',
                        'history'          => 'History',
                    ],
                    'button' => [
                        'download'        => 'Download',
                        'custom_download' => 'Custom Download',
                        'rename'          => 'Rename',
                        're_upload'       => 'Re-Upload',
                        'delete'          => 'Delete',
                    ],

                    'custom-download' => [
                        'title'              => 'Custom Download',
                        'format'             => 'Format',
                        'width'              => 'Width (px)',
                        'width-placeholder'  => '200',
                        'height'             => 'Height (px)',
                        'height-placeholder' => '200',
                        'download-btn'       => 'Download',

                        'extension-types' => [
                            'jpg'      => 'JPG',
                            'png'      => 'PNG',
                            'jpeg'     => 'JPEG',
                            'webp'     => 'WEBP',
                            'original' => 'Original',
                        ],
                    ],

                    'tag-already-exists'        => 'Tag already exists',
                    'image-source-not-readable' => 'Image source not readable',
                    'failed-to-read'            => 'Failed to read image metadata :exception',
                    'file_re_upload_success'    => 'Files Re-Uploaded Successfully.',

                ],
                'linked-resources' => [
                    'index' => [
                        'datagrid' => [
                            'product'       => 'Product',
                            'category'      => 'Category',
                            'product-sku'   => 'Product Sku: ',
                            'category code' => 'Category Code: ',
                            'resource-type' => 'Resource Type',
                            'resource'      => 'Resource',
                            'resource-view' => 'Resource View',
                        ],
                    ],
                    'found-success' => 'Resource Found Successfully',
                    'not-found'     => 'Resource Not Found',
                ],
                'tags' => [
                    'index'  => 'Add tags',
                    'create' => [
                        'create-success' => 'Tags has been successfully added',
                        'create-failure' => 'Tags failed to create',
                    ],

                    'no-comments'    => 'No Tags Yet',
                    'found-success'  => 'Tag Found Successfully',
                    'not-found'      => 'Tags Not Found',
                    'update-success' => 'Tags updated Successfully',
                    'update-failed'  => 'Tags failed to update',
                    'delete-success' => 'Asset Tags Deleted Successfully',
                    'delete-failed'  => 'Asset Tags failed to delete',

                ],
                'delete-success'                          => 'Asset deleted successfully',
                'delete-failed-due-to-attached-resources' => 'Failed to delete asset as it is linked to resources (Asset Name: :assetNames)',
                'datagrid'                                => [
                    'mass-delete-success'  => 'Mass Deleted Successfully.',
                    'files_upload_success' => 'Files Uploaded Successfully.',
                    'file_upload_success'  => 'File Uploaded Successfully.',
                    'not-found'            => 'File not Found',
                    'edit-success'         => 'File Uploaded Successfully',
                    'show-success'         => 'File Found Successfully',
                    'update-success'       => 'File Updated Successfully',
                    'not-found-to-update'  => 'File does not Exits',
                    'not-found-to-destroy' => 'File does not Exits',
                ],
            ],
        ],
        'catalog' => [
            'attributes' => [
                'type' => [
                    'asset' => 'Asset',
                ],
            ],
            'category-fields' => [
                'type' => [
                    'asset' => 'Asset',
                ],
            ],
        ],
        'acl' => [
            'menu'             => 'DAM',
            'asset'            => 'Asset',
            'property'         => 'Property',
            'comment'          => 'Comment',
            'linked_resources' => 'Linked Resources',
            'directory'        => 'Directory',
            'tag'              => 'Tag',
            'create'           => 'Create',
            'edit'             => 'Edit',
            'update'           => 'Update',
            'delete'           => 'Delete',
            'list'             => 'List',
            'view'             => 'View',
            'upload'           => 'Upload',
            're_upload'        => 'Re-Upload',
            'mass_update'      => 'Mass Update',
            'mass_delete'      => 'Mass Delete',
            'download'         => 'Download',
            'custom_download'  => 'Custom Download',
            'rename'           => 'Rename',
            'move'             => 'Move',
            'copy'             => 'Copy',
            'copy-structure'   => 'Copy Directory Structure',
            'download-zip'     => 'Download Zip',
            'asset-assign'     => 'Assign Asset',
        ],

        'validation' => [
            'asset' => [
                'required' => 'The :attribute field is required.',
            ],

            'comment' => [
                'required' => 'The Comment message is required.',
            ],
            'tag' => [
                'name' => [
                    'required' => 'The Tag field is required.',
                ],
            ],
            'property' => [
                'name' => [
                    'required' => 'The Name field is required.',
                    'unique'   => 'The Name has already been taken.',
                ],
            ],
        ],

        'errors' => [
            '401' => 'This action is unauthorized.',
        ],
    ],
];
