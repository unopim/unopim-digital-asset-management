<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadBatch extends Model
{
    const STATE_PENDING = 'pending';

    const STATE_PROCESSING = 'processing';

    const STATE_PROCESSED = 'processed';

    const STATE_FAILED = 'failed';

    const STATE_CANCELLED = 'cancelled';

    protected $table = 'dam_upload_batches';

    protected $fillable = [
        'upload_tracker_id',
        'asset_id',
        'state',
        'error',
    ];

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(UploadTracker::class, 'upload_tracker_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
