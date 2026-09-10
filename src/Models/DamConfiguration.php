<?php

declare(strict_types=1);

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;

class DamConfiguration extends Model
{
    public $timestamps = false;

    protected $table = 'dam_configuration';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const KEY_MAP = [
        'DAM_TREE_SHOW_ASSETS'           => 'dam.tree.show_assets',
        'DAM_EXPLORER_ENABLED'           => 'dam.explorer.enabled',
        'DAM_EXPLORER_BOOKMARKS_ENABLED' => 'dam.explorer.bookmarks_enabled',
        'DAM_EXPLORER_SHOW_TREE'         => 'dam.explorer.show_tree',
        'DAM_AI_TAGGING_ENABLED'         => 'dam.ai_tagging.enabled',
        'DAM_AI_TAGGING_PLATFORM_ID'     => 'dam.ai_tagging.platform_id',
        'DAM_AI_TAGGING_MAX_TAGS'        => 'dam.ai_tagging.max_tags',
        'DAM_AI_TAGGING_RATE_LIMIT'      => 'dam.ai_tagging.rate_limit_per_minute',
    ];

    /**
     * Keys in KEY_MAP whose value is not a boolean, so the DAM middleware
     * must not run it through filter_var(FILTER_VALIDATE_BOOLEAN).
     */
    public const NON_BOOLEAN_KEYS = ['DAM_AI_TAGGING_PLATFORM_ID', 'DAM_AI_TAGGING_MAX_TAGS', 'DAM_AI_TAGGING_RATE_LIMIT'];
}
