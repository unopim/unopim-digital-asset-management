<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DAM\Contracts\AssetProperty as AssetPropertyContract;
use Webkul\DAM\Database\Factories\PropertyFactory;
use Webkul\DAM\Presenters\AssetProperty as AssetPropertyPresenter;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AssetProperty extends Model implements AssetPropertyContract, HistoryAuditable, PresentableHistoryInterface
{
    use HasFactory;
    use HistoryTrait;

    protected $historyTags = ['asset'];

    protected $table = 'dam_asset_properties';

    protected $fillable = ['name', 'type', 'language', 'value', 'dam_asset_id'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'id',
        'dam_asset_id',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'dam_asset_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->dam_asset_id;
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'name'     => AssetPropertyPresenter::class,
            'type'     => AssetPropertyPresenter::class,
            'value'    => AssetPropertyPresenter::class,
            'language' => AssetPropertyPresenter::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return PropertyFactory::new();
    }
}
