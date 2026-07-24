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
        'key'   => 'dam.asset.download_compressed',
        'name'  => 'dam::app.admin.acl.download-zip',
        'route' => 'admin.dam.assets.download_compressed',
        'sort'  => 9,
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
    ], [
        'key'   => 'dam.asset.share',
        'name'  => 'dam::app.admin.acl.share',
        'route' => 'admin.dam.shared-links.store',
        'sort'  => 11,
    ],

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
        'key'   => 'dam.asset.comment.update',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.dam.asset.comment.update',
        'sort'  => 3,
    ], [
        'key'   => 'dam.asset.comment.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.asset.comment.delete',
        'sort'  => 4,
    ], [
        'key'   => 'dam.asset.meta_data',
        'name'  => 'dam::app.admin.acl.meta_data',
        'route' => 'admin.dam.assets.metadata',
        'sort'  => 16,
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
        'key'   => 'dam.directory.share',
        'name'  => 'dam::app.admin.acl.share',
        'route' => 'admin.dam.shared-links.store',
        'sort'  => 7,
    ], [
        'key'   => 'dam.asset_assign',
        'name'  => 'dam::app.admin.acl.asset-assign',
        'route' => 'admin.dam.asset_picker.get_assets',
        'sort'  => 3,
    ], [
        'key'   => 'dam.assets',
        'name'  => 'dam::app.admin.acl.assets',
        'route' => 'admin.dam.assets.index',
        'sort'  => 4,
    ], [
        'key'   => 'dam.shares',
        'name'  => 'dam::app.admin.acl.shares',
        'route' => 'admin.dam.shared-links.index',
        'sort'  => 5,
    ], [
        'key'   => 'dam.shares.index',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.shared-links.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.shares.revoke',
        'name'  => 'dam::app.admin.acl.revoke',
        'route' => 'admin.dam.shared-links.revoke',
        'sort'  => 2,
    ], [
        'key'   => 'dam.shares.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.shared-links.destroy',
        'sort'  => 3,
    ], [
        'key'   => 'dam.tags',
        'name'  => 'dam::app.admin.acl.tags',
        'route' => 'admin.dam.tags.index',
        'sort'  => 6,
    ], [
        'key'   => 'dam.tags.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.dam.tags.store',
        'sort'  => 1,
    ], [
        'key'   => 'dam.tags.update',
        'name'  => 'dam::app.admin.acl.update',
        'route' => 'admin.dam.tags.update',
        'sort'  => 2,
    ], [
        'key'   => 'dam.tags.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.dam.tags.destroy',
        'sort'  => 3,
    ], [
        'key'   => 'dam.configuration',
        'name'  => 'dam::app.admin.acl.configuration',
        'route' => 'admin.dam.configuration.index',
        'sort'  => 6,
    ], [
        'key'   => 'dam.configuration.index',
        'name'  => 'dam::app.admin.acl.view',
        'route' => 'admin.dam.configuration.index',
        'sort'  => 1,
    ], [
        'key'   => 'dam.configuration.update',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.dam.configuration.update',
        'sort'  => 2,
    ],
];
