<?php

return [
    [
        'key'    => 'dam',
        'name'   => 'dam::app.admin.components.layouts.sidebar.dam',
        'route'  => 'admin.dam.index',
        'sort'   => 10,
        'icon'   => 'icon-dam-menu',
        'module' => 'dam',
    ],
    [
        'key'    => 'dam.assets',
        'name'   => 'dam::app.admin.components.layouts.sidebar.assets',
        'route'  => 'admin.dam.assets.index',
        'sort'   => 1,
        'icon'   => '',
        'module' => 'dam',
    ],
    [
        'key'    => 'dam.tags',
        'name'   => 'dam::app.admin.components.layouts.sidebar.tags',
        'route'  => 'admin.dam.tags.index',
        'sort'   => 2,
        'icon'   => '',
        'module' => 'dam',
    ],
    [
        'key'    => 'dam.shares',
        'name'   => 'dam::app.admin.components.layouts.sidebar.shares',
        'route'  => 'admin.dam.shared-links.index',
        'sort'   => 3,
        'icon'   => '',
        'module' => 'dam',
    ],
    [
        'key'    => 'dam.configuration',
        'name'   => 'dam::app.admin.components.layouts.sidebar.configuration',
        'route'  => 'admin.dam.configuration.index',
        'sort'   => 4,
        'icon'   => '',
        'module' => 'dam',
    ],
];
