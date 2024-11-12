<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DAM\Contracts\Asset as AssetContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Asset extends Model implements AssetContract, HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['asset'];

    protected $table = 'dam_assets';

    protected $fillable = ['file_name', 'file_type', 'file_size', 'path', 'mime_type', 'extension'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'dam_asset_tag');
    }

    public function directories()
    {
        return $this->belongsToMany(Directory::class, 'dam_asset_directory');
    }

    public function properties()
    {
        return $this->hasMany(AssetProperty::class, 'dam_asset_id');
    }

    public function comments()
    {
        return $this->hasMany(AssetComments::class, 'dam_asset_id')
            ->whereNull('parent_id')
            ->with(['children']);
    }

    public function resources()
    {
        return $this->hasMany(AssetResourceMapping::class, 'dam_asset_id');
    }
}
