<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\Share as ShareContract;
use Webkul\DAM\Database\Factories\ShareFactory;
use Webkul\User\Models\Admin;

class Share extends Model implements ShareContract
{
    use HasFactory;

    public const TYPE_ASSET = 'asset';

    public const TYPE_DIRECTORY = 'directory';

    protected $table = 'dam_shares';

    protected $fillable = [
        'token',
        'share_type',
        'target_id',
        'created_by',
        'expires_at',
        'revoked_at',
        'is_active',
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
        'is_active'        => 'boolean',
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
        return ! $this->is_active;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function canBeEnabled(): bool
    {
        return ! $this->is_active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if (! $this->is_active) {
            return 'revoked';
        }

        return 'active';
    }

    protected static function newFactory(): Factory
    {
        return ShareFactory::new();
    }
}
