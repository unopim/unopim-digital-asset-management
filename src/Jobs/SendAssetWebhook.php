<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Jobs\Concerns\InteractsWithAssetWebhook;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\AssetWebhookDispatcher;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\WebhookRepository;

class SendAssetWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithAssetWebhook, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        protected int $assetId,
        protected string $event,
        protected ?int $userId = null,
    ) {}

    public function handle(WebhookRepository $webhookRepository, LogsRepository $logsRepository): void
    {
        $webhooks = $webhookRepository->getActiveForEvent($this->event);

        if ($webhooks->isEmpty()) {
            return;
        }

        $asset = Asset::with(['directories', 'tags'])->find($this->assetId);

        if (! $asset) {
            return;
        }

        $payload = [
            'event'     => $this->event,
            'timestamp' => now()->toDateTimeString(),
            'data'      => [$this->normalizeAsset($asset)],
        ];

        $sku = $asset->path ?: (string) $asset->id;

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $payload, $sku, $logsRepository);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function events(): array
    {
        return [
            AssetWebhookDispatcher::EVENT_ASSET_CREATED,
            AssetWebhookDispatcher::EVENT_ASSET_UPDATED,
        ];
    }
}
