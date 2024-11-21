<?php

return [
    [
        'key'   => 'dam',
        'name'  => 'dam::app.admin.acl.menu',
        'route' => 'admin.dam.index',
        'sort'  => 11,
    ], [
        'key'   => 'dam.asset',
        'name'  => 'dam::app.admin.acl.asset',
        'route' => 'admin.dam.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.asset.edit',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.dam.assets.edit',
        'sort'  => 1,
    ], [
        'key'   => 'dam.asset.view',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.assets.show',
        'sort'  => 2,
    ], [
        'key'   => 'dam.asset.update',
        'name'  => 'dam::app.admin.acl.update',
        'route' => 'admin.dam.assets.update',
        'sort'  => 3,
    ], [
        'key'   => 'dam.asset.upload',
        'name'  => 'dam::app.admin.acl.upload',
        'route' => 'admin.dam.assets.upload',
        'sort'  => 4,
    ], [
        'key'   => 'dam.asset.re_upload',
        'name'  => 'dam::app.admin.acl.re_upload',
        'route' => 'admin.dam.assets.re_upload',
        'sort'  => 5,
    ], [
        'key'   => 'dam.asset.destroy',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.assets.destroy',
        'sort'  => 6,
    ], [
        'key'   => 'dam.asset.mass_delete',
        'name'  => 'dam::app.admin.acl.mass_delete',
        'route' => 'admin.dam.assets.mass_delete',
        'sort'  => 7,
    ], [
        'key'   => 'dam.asset.download',
        'name'  => 'dam::app.admin.acl.download',
        'route' => 'admin.dam.assets.download',
        'sort'  => 8,
    ], [
        'key'   => 'dam.asset.rename',
        'name'  => 'dam::app.admin.acl.rename',
        'route' => 'admin.dam.assets.rename',
        'sort'  => 9,
    ], [
        'key'   => 'dam.asset.moved',
        'name'  => 'dam::app.admin.acl.move',
        'route' => 'admin.dam.assets.moved',
        'sort'  => 10,
    ],

    // @TODO: Tag ACL implements the future
    // [
    //     'key'   => 'dam.asset.tag',
    //     'name'  => 'dam::app.admin.acl.tag',
    //     'route' => 'admin.dam.assets.tag',
    //     'sort'  => 12,
    // ], [
    //     'key'   => 'dam.asset.tag.create',
    //     'name'  => 'dam::app.admin.acl.create',
    //     'route' => 'admin.dam.assets.tag',
    //     'sort'  => 1,
    // ], [
    //     'key'   => 'dam.asset.tag.delete',
    //     'name'  => 'dam::app.admin.acl.delete',
    //     'route' => 'admin.dam.assets.remove-tag',
    //     'sort'  => 2,
    // ],

    [
        'key'   => 'dam.asset.property',
        'name'  => 'dam::app.admin.acl.property',
        'route' => 'admin.dam.asset.properties.index',
        'sort'  => 13,
    ], [
        'key'   => 'dam.asset.property.view',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.asset.properties.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.asset.property.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.dam.asset.property.store',
        'sort'  => 2,
    ], [
        'key'   => 'dam.asset.property.update',
        'name'  => 'dam::app.admin.acl.update',
        'route' => 'admin.dam.asset.properties.update',
        'sort'  => 3,
    ], [
        'key'   => 'dam.asset.property.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.asset.properties.delete',
        'sort'  => 4,
    ], [
        'key'   => 'dam.asset.comment',
        'name'  => 'dam::app.admin.acl.comment',
        'route' => 'admin.dam.asset.comments.index',
        'sort'  => 14,
    ], [
        'key'   => 'dam.asset.comment.index',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.asset.comments.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.asset.comment.store',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.dam.asset.comment.store',
        'sort'  => 2,
    ], [
        'key'   => 'dam.asset.linked_resources',
        'name'  => 'dam::app.admin.acl.linked_resources',
        'route' => 'admin.dam.asset.linked_resources.index',
        'sort'  => 15,
    ], [
        'key'   => 'dam.asset.linked_resources.index',
        'name'  => 'dam::app.admin.acl.linked_resources',
        'route' => 'admin.dam.asset.linked_resources.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.directory',
        'name'  => 'dam::app.admin.acl.directory',
        'route' => 'admin.dam.directory.index',
        'sort'  => 2,
    ], [
        'key'   => 'dam.directory.index',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.directory.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.directory.store',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.dam.directory.store',
        'sort'  => 2,
    ], [
        'key'   => 'dam.directory.rename',
        'name'  => 'dam::app.admin.acl.rename',
        'route' => 'admin.dam.directory.update',
        'sort'  => 3,
    ], [
        'key'   => 'dam.directory.destroy',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.directory.destroy',
        'sort'  => 4,
    ], [
        'key'   => 'dam.directory.copy_structure',
        'name'  => 'dam::app.admin.acl.copy-structure',
        'route' => 'admin.dam.directory.copy_structure',
        'sort'  => 5,
    ], [
        'key'   => 'dam.directory.download_zip',
        'name'  => 'dam::app.admin.acl.download-zip',
        'route' => 'admin.dam.directory.zip_download',
        'sort'  => 5,
    ], [
        'key'   => 'dam.directory.moved',
        'name'  => 'dam::app.admin.acl.move',
        'route' => 'admin.dam.directory.moved',
        'sort'  => 6,
    ], [
        'key'   => 'dam.asset_assign',
        'name'  => 'dam::app.admin.acl.asset-assign',
        'route' => 'admin.dam.asset_picker.get_assets',
        'sort'  => 3,
    ],
];
