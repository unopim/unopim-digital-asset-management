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
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\WebhookRepository;

class SendBulkAssetWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithAssetWebhook, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<int, int>  $assetIds
     */
    public function __construct(
        protected array $assetIds,
        protected string $event,
        protected ?int $userId = null,
    ) {}

    public function handle(WebhookRepository $webhookRepository, LogsRepository $logsRepository): void
    {
        if (empty($this->assetIds)) {
            return;
        }

        $webhooks = $webhookRepository->getActiveForEvent($this->event);

        if ($webhooks->isEmpty()) {
            return;
        }

        $assets = Asset::with(['directories', 'tags'])->findMany($this->assetIds);

        if ($assets->isEmpty()) {
            return;
        }

        $payload = [
            'event'     => $this->event,
            'timestamp' => now()->toDateTimeString(),
            'data'      => $assets->map(fn (Asset $asset): array => $this->normalizeAsset($asset))->all(),
        ];

        $sku = 'bulk ('.$assets->count().' assets)';

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $payload, $sku, $logsRepository);
        }
    }
}
