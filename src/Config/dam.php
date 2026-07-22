<?php

declare(strict_types=1);

return [

    'tree' => [
        'show_assets' => env('DAM_TREE_SHOW_ASSETS', false),
    ],

    'explorer' => [
        'enabled'           => env('DAM_EXPLORER_ENABLED', false),
        'bookmarks_enabled' => env('DAM_EXPLORER_BOOKMARKS_ENABLED', false),
        'show_tree'         => env('DAM_EXPLORER_SHOW_TREE', true),

        'upload' => [
            'concurrency'        => (int) env('DAM_UPLOAD_CONCURRENCY', 4),
            'resume_enabled'     => env('DAM_UPLOAD_RESUME_ENABLED', true),
            'resume_max_bytes'   => (int) env('DAM_UPLOAD_RESUME_MAX_BYTES', 524288000),
            'resume_stale_hours' => (int) env('DAM_UPLOAD_RESUME_STALE_HOURS', 24),
        ],
    ],

];
