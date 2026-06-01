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
    ];
}
