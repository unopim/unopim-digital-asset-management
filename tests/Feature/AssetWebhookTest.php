<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Webkul\DAM\Jobs\SendAssetWebhook;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\AssetWebhookDispatcher;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Registry\EventRegistry;
use Webkul\Webhook\Repositories\LogsRepository;
use Webkul\Webhook\Repositories\WebhookRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    AssetWebhookDispatcher::flushAvailability();
    Webhook::query()->delete();
    WebhookLog::query()->delete();
});

function makeWebhook(array $events): Webhook
{
    return Webhook::create([
        'name'      => 'Asset hook',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => true,
        'events'    => $events,
    ]);
}

it('registers the asset events in the webhook event registry', function () {
    $registry = app(EventRegistry::class);

    expect($registry->has('asset.created'))->toBeTrue()
        ->and($registry->has('asset.updated'))->toBeTrue()
        ->and($registry->keys())->toContain('asset.created', 'asset.updated');

    $labels = collect($registry->forOptions())->pluck('label');

    expect($labels)->toContain('Asset Created', 'Asset Updated');
});

it('queues an asset.created webhook when a webhook subscribes', function () {
    Queue::fake();

    makeWebhook(['asset.created']);

    $asset = Asset::factory()->create();

    Queue::assertPushed(SendAssetWebhook::class, function (SendAssetWebhook $job) use ($asset) {
        return (new ReflectionProperty($job, 'assetId'))->getValue($job) === $asset->id
            && (new ReflectionProperty($job, 'event'))->getValue($job) === 'asset.created';
    });
});

it('queues an asset.updated webhook on update', function () {
    Queue::fake();

    makeWebhook(['asset.updated']);

    $asset = Asset::factory()->create();
    $asset->update(['file_name' => 'renamed.jpg']);

    Queue::assertPushed(SendAssetWebhook::class, function (SendAssetWebhook $job) {
        return (new ReflectionProperty($job, 'event'))->getValue($job) === 'asset.updated';
    });
});

it('does not queue a webhook when none subscribes to the event', function () {
    Queue::fake();

    makeWebhook(['asset.updated']); // only updated — create must not fire

    Asset::factory()->create();

    Queue::assertNotPushed(SendAssetWebhook::class);
});

it('does not queue a webhook when the only subscriber is inactive', function () {
    Queue::fake();

    Webhook::create([
        'name'      => 'Inactive',
        'url'       => 'https://8.8.8.8/hook',
        'is_active' => false,
        'events'    => ['asset.created'],
    ]);

    Asset::factory()->create();

    Queue::assertNotPushed(SendAssetWebhook::class);
});

it('delivers a signed payload to the subscribed webhook and logs it', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $webhook = makeWebhook(['asset.created']);
    $webhook->update(['secret' => 'shh']);

    $asset = Asset::factory()->create();

    (new SendAssetWebhook($asset->id, 'asset.created'))
        ->handle(app(WebhookRepository::class), app(LogsRepository::class));

    Http::assertSent(function ($request) use ($asset) {
        return $request->url() === 'https://8.8.8.8/hook'
            && $request->header('X-Unopim-Event')[0] === 'asset.created'
            && str_starts_with($request->header('X-Unopim-Signature')[0] ?? '', 'sha256=')
            && str_contains($request->body(), '"id":'.$asset->id);
    });

    $log = WebhookLog::where('webhook_id', $webhook->id)->where('event', 'asset.created')->first();

    expect($log)->not->toBeNull()
        ->and((int) $log->status)->toBe(1)
        ->and((int) $log->http_code)->toBe(200);
});
