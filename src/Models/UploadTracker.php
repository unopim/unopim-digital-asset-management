<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Background asset-upload session mirroring the core DataTransfer job_track state machine. */
class UploadTracker extends Model
{
    const STATE_PENDING = 'pending';

    const STATE_PROCESSING = 'processing';

    const STATE_PAUSED = 'paused';

    const STATE_COMPLETED = 'completed';

    const STATE_CANCELLED = 'cancelled';

    const STATE_FAILED = 'failed';

    protected $table = 'dam_upload_trackers';

    protected $fillable = [
        'uuid',
        'user_id',
        'directory_id',
        'state',
        'total_files',
        'processed_files',
        'failed_files',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'summary'      => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Per-asset finalisation rows for this session.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(UploadBatch::class, 'upload_tracker_id');
    }

    /**
     * Whether a queued job should abort before touching the asset.
     */
    public function shouldStop(): bool
    {
        return in_array($this->state, [
            self::STATE_PAUSED,
            self::STATE_CANCELLED,
            self::STATE_FAILED,
        ], true);
    }

    /**
     * Whether the session can still make progress or be resumed.
     */
    public function isActive(): bool
    {
        return in_array($this->state, [
            self::STATE_PENDING,
            self::STATE_PROCESSING,
            self::STATE_PAUSED,
        ], true);
    }
}
