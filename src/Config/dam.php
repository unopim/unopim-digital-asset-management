<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directory Tree Settings
    |--------------------------------------------------------------------------
    |
    | Knobs that control how the DAM directory tree renders on the admin side.
    |
    */
    'tree' => [

        /*
         * Render asset leaf nodes inside the directory tree itself.
         *
         * When false (default), expanding a folder in the tree shows only
         * child directories; assets stay in the right-hand grid where the
         * datagrid handles pagination. This keeps the tree payload small
         * for installs with thousands of assets per folder.
         *
         * Flip to true via env to restore the legacy behavior where assets
         * also appear as leaf rows inline with their folder.
         */
        'show_assets' => env('DAM_TREE_SHOW_ASSETS', false),

    ],

];
