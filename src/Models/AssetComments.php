<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DAM\Contracts\AssetComments as AssetCommentsContract;
use Webkul\DAM\Database\Factories\CommentFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Models\Admin;

class AssetComments extends Model implements AssetCommentsContract, HistoryAuditable
{
    use HasFactory;
    use HistoryTrait;

    protected $historyTags = ['asset'];

    protected $auditExclude = [
        'id',
        'parent_id',
        'dam_asset_id',
    ];

    protected $table = 'dam_asset_comments';

    protected $fillable = ['admin_id', 'parent_id', 'comments', 'dam_asset_id'];

    /**
     * Get the asset this comment belongs to.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }

    /**
     * Get the admin who authored the comment.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Get the reply comments for this comment.
     *
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(AssetComments::class, 'parent_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }

    /**
     * Get the asset id used as the history primary model.
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->dam_asset_id;
    }
}
