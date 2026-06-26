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
        'show_tree'         => env('DAM_EXPLORER_SHOW_TREE', true),

        'upload' => [
            // Number of files uploaded concurrently by the panel's worker pool.
            'concurrency'        => (int) env('DAM_UPLOAD_CONCURRENCY', 4),
            // Persist pending file bytes to IndexedDB so uploads resume after refresh.
            'resume_enabled'     => env('DAM_UPLOAD_RESUME_ENABLED', true),
            // Skip byte-stashing (queue restores as "interrupted") above this batch size.
            'resume_max_bytes'   => (int) env('DAM_UPLOAD_RESUME_MAX_BYTES', 524288000), // 500 MB
            // Evict stashed bytes/metadata older than this on load.
            'resume_stale_hours' => (int) env('DAM_UPLOAD_RESUME_STALE_HOURS', 24),
        ],
    ],

];
