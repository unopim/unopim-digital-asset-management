<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Asset;
use Webkul\User\Models\AdminProxy;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Validators\SafeWebhookUrl;

trait InteractsWithAssetWebhook
{
    /**
     * @return array<string, mixed>
     */
    protected function normalizeAsset(Asset $asset): array
    {
        return [
            'id'          => $asset->id,
            'file_name'   => $asset->file_name,
            'path'        => $asset->path,
            'file_type'   => $asset->file_type,
            'mime_type'   => $asset->mime_type,
            'extension'   => $asset->extension,
            'file_size'   => $asset->file_size,
            'preview_url' => AssetHelper::getPreviewUrl($asset->path),
            'directories' => $asset->directories->map(fn ($directory) => [
                'id'   => $directory->id,
                'name' => $directory->name,
            ])->values()->all(),
            'tags'        => $asset->tags->pluck('name')->values()->all(),
            'created_at'  => $asset->created_at?->toIso8601String(),
            'updated_at'  => $asset->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function deliverWebhook(Webhook $webhook, array $payload, string $sku, LogsRepository $logsRepository): void
    {
        $safety = SafeWebhookUrl::validate($webhook->url);

        if (! $safety['valid']) {
            Log::warning('Asset webhook dispatch blocked — unsafe URL', [
                'webhook_id' => $webhook->id,
                'reason'     => $safety['reason'],
                'ip'         => $safety['ip'] ?? null,
            ]);

            return;
        }

        $body = json_encode($payload);
        $exception = null;
        $response = null;

        try {
            $response = Http::withOptions(SafeWebhookUrl::httpOptions($webhook->url))
                ->withHeaders($this->buildHeaders($webhook, $body))
                ->withBody($body, 'application/json')
                ->post($webhook->url);
        } catch (\Throwable $e) {
            $exception = $e;

            report($e);
        }

        $status = $response?->successful() ? 1 : 0;
        $httpCode = $response?->status() ?? ($exception?->getCode() ?: null);

        $logsRepository->create([
            'webhook_id' => $webhook->id,
            'user'       => $this->actingName(),
            'sku'        => $sku,
            'event'      => $this->event,
            'status'     => $status,
            'http_code'  => is_int($httpCode) ? $httpCode : null,
            'extra'      => [
                'payload'  => $payload,
                'response' => $response?->body() ?? $exception?->getMessage(),
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function buildHeaders(Webhook $webhook, string $body): array
    {
        $headers = [
            'X-Unopim-Event'      => $this->event,
            'X-Unopim-Webhook-Id' => (string) $webhook->id,
        ];

        foreach ((array) $webhook->headers as $key => $value) {
            if (is_string($key) && $key !== '') {
                $headers[$key] = (string) $value;
            }
        }

        if (! empty($webhook->secret)) {
            $headers['X-Unopim-Signature'] = 'sha256='.hash_hmac('sha256', $body, (string) $webhook->secret);
        }

        return $headers;
    }

    protected function actingName(): ?string
    {
        if ($this->userId === null || ! class_exists(AdminProxy::class)) {
            return null;
        }

        return AdminProxy::find($this->userId)?->name;
    }
}
