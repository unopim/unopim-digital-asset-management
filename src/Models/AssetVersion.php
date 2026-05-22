<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetVersion extends Model
{
    protected $table = 'dam_asset_versions';

    protected $fillable = [
        'asset_id',
        'version_path',
        'original_path',
        'original_file_name',
        'original_extension',
        'original_mime_type',
        'original_file_type',
        'original_file_size',
        'original_meta_data',
    ];

    protected $casts = [
        'original_file_size' => 'integer',
        'original_meta_data' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
