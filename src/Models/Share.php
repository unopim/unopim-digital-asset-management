<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\Share as ShareContract;
use Webkul\DAM\Database\Factories\ShareFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Models\Admin;

class Share extends Model implements HistoryContract, ShareContract
{
    use HasFactory, HistoryTrait;

    public const TYPE_ASSET = 'asset';

    public const TYPE_DIRECTORY = 'directory';

    protected $table = 'dam_shares';

    protected $historyTags = ['Share'];

    /**
     * Internal bookkeeping columns whose churn would spam the history feed.
     * The user-visible fields (name, expires_at, revoked_at, token) stay tracked.
     */
    protected $auditExclude = [
        'view_count',
        'download_count',
        'last_accessed_at',
        'updated_at',
        'created_at',
    ];

    protected $fillable = [
        'token',
        'name',
        'share_type',
        'target_id',
        'created_by',
        'expires_at',
        'revoked_at',
        'view_count',
        'download_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'revoked_at'       => 'datetime',
        'last_accessed_at' => 'datetime',
        'view_count'       => 'integer',
        'download_count'   => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'target_id');
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'target_id');
    }

    /**
     * Resolve the underlying target model regardless of share_type.
     */
    public function target(): ?Model
    {
        return $this->share_type === self::TYPE_ASSET
            ? $this->asset
            : $this->directory;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function statusLabel(): string
    {
        if ($this->isRevoked()) {
            return 'revoked';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'active';
    }

    protected static function newFactory(): Factory
    {
        return ShareFactory::new();
    }
}
