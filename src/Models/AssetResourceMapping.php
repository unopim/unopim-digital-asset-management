<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\Adapters\CategoryResolver;
use Webkul\DAM\Contracts\Adapters\ProductResolver;
use Webkul\DAM\Contracts\AssetResourceMapping as AssetResourceMappingContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AssetResourceMapping extends Model implements AssetResourceMappingContract, HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['asset'];

    protected $auditExclude = [
        'id',
    ];

    protected $table = 'dam_asset_resource_mappings';

    protected $fillable = [
        'type',
        'related_field',
        'dam_asset_id',
        'product_id',
        'category_id',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }

    public function product(): BelongsTo
    {
        $related = app(ProductResolver::class)->modelClass() ?? self::class;

        return $this->belongsTo($related, 'product_id');
    }

    public function category(): BelongsTo
    {
        $related = app(CategoryResolver::class)->modelClass() ?? self::class;

        return $this->belongsTo($related, 'category_id');
    }

    public function getPrimaryModelIdForHistory(): int
    {
        return $this->dam_asset_id;
    }
}
