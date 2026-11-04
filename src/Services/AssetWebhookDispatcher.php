<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Jobs\SendAssetWebhook;
use Webkul\DAM\Models\Asset;
use Webkul\Webhook\Repositories\WebhookRepository;

/**
 * Bridges DAM asset lifecycle events to the webhook delivery core.
 *
 * It fires only when the webhook package is installed and a webhook is
 * actually subscribed to the event, so an asset save costs nothing extra
 * when webhooks are unused. Queued delivery lives in {@see SendAssetWebhook}.
 */
class AssetWebhookDispatcher
{
    public const EVENT_ASSET_CREATED = 'asset.created';

    public const EVENT_ASSET_UPDATED = 'asset.updated';

    protected static ?bool $available = null;

    public function created(Asset $asset): void
    {
        $this->fire($asset, self::EVENT_ASSET_CREATED);
    }

    public function updated(Asset $asset): void
    {
        $this->fire($asset, self::EVENT_ASSET_UPDATED);
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

    /**
     * Whether the webhook package is installed and migrated. The result is
     * cached for the request so repeated asset saves pay the schema check
     * only once.
     */
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

    /**
     * Reset the cached availability flag (tests).
     */
    public static function flushAvailability(): void
    {
        self::$available = null;
    }
}
