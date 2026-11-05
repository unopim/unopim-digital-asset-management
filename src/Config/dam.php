<?php

declare(strict_types=1);

return [

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
        'model'                 => env('DAM_AI_TAGGING_MODEL'),
        'max_tags'              => (int) env('DAM_AI_TAGGING_MAX_TAGS', 8),
        'max_file_size'         => (int) env('DAM_AI_TAGGING_MAX_FILE_SIZE', 10 * 1024 * 1024),
        'rate_limit_per_minute' => (int) env('DAM_AI_TAGGING_RATE_LIMIT_PER_MINUTE', 60),

        'vision_model_patterns' => [
            '/^(?:.*\/)?(?:chat)?gpt-4o/i',
            '/^(?:.*\/)?gpt-4\.(?:1|5)/i',
            '/^(?:.*\/)?gpt-4-(?:turbo|vision)/i',
            '/^(?:.*\/)?gpt-(?:[5-9]|\d{2,})/i',
            '/^(?:.*\/)?o(?:1|3)(?!-mini)(?:-|$)/i',
            '/^(?:.*\/)?o4-mini/i',
            '/claude-(?:3|[4-9]|haiku-[4-9]|sonnet-[4-9]|opus-[4-9])/i',
            '/gemini-(?!1\.0)/i',
            '/gemma-3/i',
            '/grok-(?:[4-9]|\d{2,})/i',
            '/llama-4/i',
            '/llava/i',
            '/pixtral/i',
            '/mistral-medium/i',
            '/mistral-small-(?:latest|3\.[1-9]|25\d\d)/i',
            '/qwen.*-vl/i',
            '/qvq/i',
            '/vision/i',
        ],
    ],

    'image_edit' => [
        'generation_only_model_patterns' => [
            '/dall-e-3/i',
            '/imagen/i',
            '/grok-2-image/i',
        ],
    ],

    'import_bundle' => [
        'max_entry_size'        => (int) env('DAM_IMPORT_BUNDLE_MAX_ENTRY_SIZE', 524288000),
        'max_total_size'        => (int) env('DAM_IMPORT_BUNDLE_MAX_TOTAL_SIZE', 5368709120),
        'max_entries'           => (int) env('DAM_IMPORT_BUNDLE_MAX_ENTRIES', 50000),
        'max_compression_ratio' => (float) env('DAM_IMPORT_BUNDLE_MAX_COMPRESSION_RATIO', 200),
    ],

];
