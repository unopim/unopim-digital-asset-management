<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Jobs\SendAssetWebhook;
use Webkul\DAM\Jobs\SendBulkAssetWebhook;
use Webkul\DAM\Models\Asset;
use Webkul\Webhook\Repositories\WebhookRepository;

class AssetWebhookDispatcher
{
    public const EVENT_ASSET_CREATED = 'asset.created';

    public const EVENT_ASSET_UPDATED = 'asset.updated';

    /**
     * Columns whose change is worth a webhook. System re-saves (meta_data,
     * thumbnails) are excluded so a single upload does not emit asset.updated
     * right after asset.created.
     *
     * @var array<int, string>
     */
    protected const MEANINGFUL_COLUMNS = ['file_name', 'path', 'file_type', 'mime_type', 'extension', 'file_size'];

    protected static ?bool $available = null;

    protected static bool $batching = false;

    /** @var array<int, int> */
    protected static array $batchCreated = [];

    /** @var array<int, int> */
    protected static array $batchUpdated = [];

    public function created(Asset $asset): void
    {
        if (self::$batching) {
            self::$batchCreated[] = (int) $asset->id;

            return;
        }

        $this->fire($asset, self::EVENT_ASSET_CREATED);
    }

    public function updated(Asset $asset): void
    {
        if (! $asset->wasChanged(self::MEANINGFUL_COLUMNS)) {
            return;
        }

        if (self::$batching) {
            self::$batchUpdated[] = (int) $asset->id;

            return;
        }

        $this->fire($asset, self::EVENT_ASSET_UPDATED);
    }

    /**
     * Suppress per-row webhooks and accumulate ids for one batch dispatch.
     * Called when an asset import batch starts.
     */
    public static function beginBatch(): void
    {
        self::$batching = true;
        self::$batchCreated = [];
        self::$batchUpdated = [];
    }

    /**
     * Flush the accumulated batch as at most one job per event, then stop
     * batching. Called when an asset import batch finishes.
     */
    public static function flushBatch(?int $userId = null): void
    {
        if (! self::$batching) {
            return;
        }

        $created = array_values(array_unique(self::$batchCreated));
        $updated = array_values(array_unique(self::$batchUpdated));

        self::$batching = false;
        self::$batchCreated = [];
        self::$batchUpdated = [];

        if (! self::webhooksAvailable()) {
            return;
        }

        $repository = app(WebhookRepository::class);

        if (! empty($created) && $repository->hasActiveForEvent(self::EVENT_ASSET_CREATED)) {
            SendBulkAssetWebhook::dispatch($created, self::EVENT_ASSET_CREATED, $userId)->onQueue('webhooks');
        }

        if (! empty($updated) && $repository->hasActiveForEvent(self::EVENT_ASSET_UPDATED)) {
            SendBulkAssetWebhook::dispatch($updated, self::EVENT_ASSET_UPDATED, $userId)->onQueue('webhooks');
        }
    }

    protected function fire(Asset $asset, string $event): void
    {
        if (! self::webhooksAvailable()) {
            return;
        }

        if (! app(WebhookRepository::class)->hasActiveForEvent($event)) {
            return;
        }

        $userId = auth('admin')->id() ?? auth('api')->id();

        SendAssetWebhook::dispatch($asset->id, $event, $userId)->onQueue('webhooks');
    }

    public static function webhooksAvailable(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        if (! class_exists(WebhookRepository::class)) {
            return self::$available = false;
        }

        try {
            return self::$available = Schema::hasTable('webhooks');
        } catch (\Throwable) {
            return self::$available = false;
        }
    }

    public static function flushAvailability(): void
    {
        self::$available = null;
    }
}
