<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\DAM\Contracts\Asset as AssetContract;
use Webkul\DAM\Database\Factories\AssetFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Asset extends Model implements AssetContract, HistoryAuditable
{
    use HasFactory;
    use HistoryTrait;

    const ASSET_ATTRIBUTE_TYPE = 'asset';

    protected $historyTags = ['asset'];

    protected $table = 'dam_assets';

    protected $fillable = ['file_name', 'file_type', 'file_size', 'path', 'mime_type', 'extension'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'id',
    ];

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

    /**
     * Get the path without file system root
     */
    public function getPathWithOutFileSystemRoot()
    {
        return str_replace(Directory::ASSETS_DIRECTORY.'/', '', $this->path);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AssetFactory::new();
    }
}
