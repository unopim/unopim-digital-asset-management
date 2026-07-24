<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\DAM\Contracts\Tag as TagContract;

class Tag extends Model implements TagContract
{
    protected $table = 'dam_tags';

    protected $fillable = ['name'];

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'dam_asset_tag', 'tag_id', 'asset_id');
    }
}
