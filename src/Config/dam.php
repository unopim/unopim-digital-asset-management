<?php

declare(strict_types=1);

return [

    /**
     * Queues DAM jobs dispatch onto. Default to Laravel's 'default' queue so an
     * existing worker that only runs `queue:work` (no --queue flag) keeps processing
     * everything with no config change; set these to route DAM's own workload
     * (fast DB housekeeping, filesystem/DB-tree bulk ops, CPU-heavy media processing)
     * onto dedicated queues instead.
     */
    'queues' => [
        'dam'   => env('DAM_QUEUE_DAM', 'default'),
        'bulk'  => env('DAM_QUEUE_BULK', 'default'),
        'media' => env('DAM_QUEUE_MEDIA', 'default'),
    ],

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

    'ai_tagging' => [
        'enabled'               => env('DAM_AI_TAGGING_ENABLED', false),
        'platform_id'           => env('DAM_AI_TAGGING_PLATFORM_ID'),
        'max_tags'              => (int) env('DAM_AI_TAGGING_MAX_TAGS', 8),
        'max_file_size'         => (int) env('DAM_AI_TAGGING_MAX_FILE_SIZE', 10 * 1024 * 1024),
        'rate_limit_per_minute' => (int) env('DAM_AI_TAGGING_RATE_LIMIT_PER_MINUTE', 60),
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
