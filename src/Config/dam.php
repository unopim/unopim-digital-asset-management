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

    /**
     * Limits applied to an export archive uploaded to an import job. These are far wider
     * than the product-images equivalent because a DAM bundle legitimately carries video
     * and other large binaries; they exist to bound zip-bomb damage, not to size assets.
     */
    'import_bundle' => [
        'max_entry_size'        => (int) env('DAM_IMPORT_BUNDLE_MAX_ENTRY_SIZE', 524288000),
        'max_total_size'        => (int) env('DAM_IMPORT_BUNDLE_MAX_TOTAL_SIZE', 5368709120),
        'max_entries'           => (int) env('DAM_IMPORT_BUNDLE_MAX_ENTRIES', 50000),
        'max_compression_ratio' => (float) env('DAM_IMPORT_BUNDLE_MAX_COMPRESSION_RATIO', 200),
    ],

];
