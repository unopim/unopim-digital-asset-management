<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DAM
    |--------------------------------------------------------------------------
    |
    | All ACLs related to DAM will be placed here.
    |
    */
    [
        'key'   => 'api.dam',
        'name'  => 'dam::app.admin.dam.index.title',
        'route' => 'admin.api.dam.assets.index',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.assets',
        'name'  => 'dam::app.admin.acl.asset',
        'route' => 'admin.api.dam.assets.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.assets.edit',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.api.dam.assets.edit',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.assets.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.api.dam.assets.destroy',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.assets.upload',
        'name'  => 'dam::app.admin.acl.upload',
        'route' => 'admin.api.dam.assets.upload',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.assets.re-upload',
        'name'  => 'dam::app.admin.acl.re_upload',
        'route' => 'admin.api.dam.assets.reUpload',
        'sort'  => 4,
    ], [
        'key'   => 'api.dam.assets.download',
        'name'  => 'dam::app.admin.acl.download',
        'route' => 'admin.api.dam.assets.download',
        'sort'  => 5,
    ], [
        'key'   => 'api.dam.assets.getById',
        'name'  => 'dam::app.admin.dam.index.directory.actions.get-by-id',
        'route' => 'admin.api.dam.assets.show',
        'sort'  => 6,
    ], [
        'key'   => 'api.dam.directory',
        'name'  => 'dam::app.admin.acl.directory',
        'route' => 'admin.api.dam.directory.index',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.directory.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.api.dam.directory.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.directory.edit',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.api.dam.directory.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.directory.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.api.dam.directory.delete',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.directory.getById',
        'name'  => 'dam::app.admin.dam.index.directory.actions.get-by-id',
        'route' => 'admin.api.dam.directory.get',
        'sort'  => 4,
    ], [
        'key'   => 'api.dam.comment',
        'name'  => 'dam::app.admin.dam.index.directory.actions.comment',
        'route' => 'admin.api.dam.comment.get',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.comment.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.api.dam.comment.store',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.comment.edit',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.api.dam.comment.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.comment.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.api.dam.comment.delete',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.comment.getById',
        'name'  => 'dam::app.admin.dam.index.directory.actions.get-by-id',
        'route' => 'admin.api.dam.comment.get',
        'sort'  => 4,
    ], [
        'key'   => 'api.dam.property',
        'name'  => 'dam::app.admin.acl.property',
        'route' => '',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.property.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.api.dam.property.add',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.property.edit',
        'name'  => 'dam::app.admin.acl.edit',
        'route' => 'admin.api.dam.property.update',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.property.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.api.dam.property.delete',
        'sort'  => 3,
    ], [
        'key'   => 'api.dam.tags',
        'name'  => 'dam::app.admin.dam.index.datagrid.tags',
        'route' => 'admin.api.dam.tags.get',
        'sort'  => 4,
    ], [
        'key'   => 'api.dam.tags.create',
        'name'  => 'dam::app.admin.acl.create',
        'route' => 'admin.api.dam.tag.add',
        'sort'  => 1,
    ], [
        'key'   => 'api.dam.tags.delete',
        'name'  => 'dam::app.admin.acl.delete',
        'route' => 'admin.api.dam.tag.delete',
        'sort'  => 2,
    ], [
        'key'   => 'api.dam.linked-resource',
        'name'  => 'dam::app.admin.dam.index.directory.linked-resources',
        'route' => 'admin.api.dam.linked_resource.get',
        'sort'  => 5,
    ], [
        'key'   => 'api.dam.linked-resource.get',
        'name'  => 'dam::app.admin.dam.index.directory.linked-resources',
        'route' => 'admin.api.dam.linked_resource.get',
        'sort'  => 1,
    ],
];
