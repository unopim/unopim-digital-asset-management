<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\AssetComments as AssetCommentsContract;

class AssetComments extends Model implements AssetCommentsContract
{
    protected $table = 'dam_asset_comments';

    protected $fillable = ['admin_id', 'parent_id', 'comments', 'dam_asset_id'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }

    public function children()
    {
        return $this->hasMany(AssetComments::class, 'parent_id');
    }
}
