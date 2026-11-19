<?php

use Illuminate\Support\Facades\Queue;
use Webkul\DAM\Jobs\SendAssetWebhook;
use Webkul\DAM\Jobs\SendBulkAssetWebhook;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\AssetWebhookDispatcher;
use Webkul\Webhook\Models\Webhook;

beforeEach(function () {
    AssetWebhookDispatcher::flushAvailability();
    AssetWebhookDispatcher::flushBatch(null);

    Webhook::query()->delete();

    Webhook::create([
        'name'      => 'asset-hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['asset.created', 'asset.updated'],
    ]);

    Queue::fake();
});

it('dispatches asset.created once and skips a meta_data-only update', function () {
    $asset = Asset::factory()->create();

    Queue::assertPushed(SendAssetWebhook::class, 1);

    $asset->update(['meta_data' => ['width' => 10, 'height' => 10]]);

    Queue::assertPushed(SendAssetWebhook::class, 1);
    Queue::assertNotPushed(SendBulkAssetWebhook::class);
});

it('dispatches asset.updated on a meaningful column change', function () {
    $asset = Asset::factory()->create();

    $asset->update(['file_name' => 'renamed.jpg']);

    Queue::assertPushed(SendAssetWebhook::class, 2);
});

it('suppresses per-row webhooks during a batch and sends one bulk job', function () {
    AssetWebhookDispatcher::beginBatch();

    Asset::factory()->create();
    Asset::factory()->create();
    Asset::factory()->create();

    Queue::assertNotPushed(SendAssetWebhook::class);

    AssetWebhookDispatcher::flushBatch(null);

    Queue::assertPushed(SendBulkAssetWebhook::class, 1);
});
