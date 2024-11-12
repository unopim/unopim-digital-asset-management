<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Category\Models\CategoryProxy;
use Webkul\DAM\Contracts\AssetResourceMapping as AssetResourceMappingContract;
use Webkul\Product\Models\ProductProxy;

class AssetResourceMapping extends Model implements AssetResourceMappingContract
{
    protected $table = 'dam_asset_resource_mappings';

    protected $fillable = [
        'type',
        'related_field',
        'dam_asset_id',
        'product_id',
        'category_id',
    ];

    /**
     * Get the asset associated with the mapping.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }

    /**
     * Get the product associated with the mapping.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::class, 'product_id');
    }

    /**
     * Get the category associated with the mapping.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryProxy::class, 'category_id');
    }
}
