<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Directory Tree Settings
    |--------------------------------------------------------------------------
    */
    'tree' => [
        'show_assets' => env('DAM_TREE_SHOW_ASSETS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Explorer Settings
    |--------------------------------------------------------------------------
    */
    'explorer' => [
        'enabled'           => env('DAM_EXPLORER_ENABLED', false),
        'bookmarks_enabled' => env('DAM_EXPLORER_BOOKMARKS_ENABLED', false),
    ],

];
