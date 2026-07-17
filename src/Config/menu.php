<?php

return [
    [
        'key'   => 'dam',
        'name'  => 'dam::app.admin.components.layouts.sidebar.dam',
        'route' => 'admin.dam.index',
        'sort'  => 10,
        'icon'  => 'icon-dam-menu',
    ],
    [
        'key'   => 'dam.assets',
        // Distinct URL so this item is not a prefix-match of the sibling DAM routes.
        'name'  => 'dam::app.admin.components.layouts.sidebar.assets',
        'route' => 'admin.dam.assets.index',
        'sort'  => 1,
        'icon'  => '',
    ],
    [
        'key'   => 'dam.tags',
        'name'  => 'dam::app.admin.components.layouts.sidebar.tags',
        'route' => 'admin.dam.tags.index',
        'sort'  => 2,
        'icon'  => '',
    ],
    [
        'key'   => 'dam.shares',
        'name'  => 'dam::app.admin.components.layouts.sidebar.shares',
        'route' => 'admin.dam.shares.index',
        'sort'  => 3,
        'icon'  => '',
    ],
    [
        'key'   => 'dam.configuration',
        'name'  => 'dam::app.admin.components.layouts.sidebar.configuration',
        'route' => 'admin.dam.configuration.index',
        'sort'  => 4,
        'icon'  => '',
    ],
];
