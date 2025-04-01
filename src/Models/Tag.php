<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\Tag as TagContract;

class Tag extends Model implements TagContract
{
    protected $table = 'dam_tags';

    protected $fillable = ['name'];

    public function assets(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }
}
