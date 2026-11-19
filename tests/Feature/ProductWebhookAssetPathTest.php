<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\WebhookService as DamWebhookService;
use Webkul\Product\Models\Product;
use Webkul\PublicUrl\Providers\PublicUrlServiceProvider;
use Webkul\Webhook\Services\WebhookService as BaseWebhookService;

uses(DatabaseTransactions::class);

/**
 * Run a `changes` diff through DAM's webhook normaliser and return the rewritten
 * diff, exactly as the outgoing webhook body would carry it.
 */
function damNormalizeWebhookChanges(array $changes): array
{
    $product = Product::factory()->simple()->create();

    $service = app(DamWebhookService::class);

    $method = (new ReflectionMethod($service, 'normalizeWebhookData'));
    $method->setAccessible(true);

    return $method->invoke($service, $product, $changes)['changes'];
}

function damAssetAttributeCode(): string
{
    $code = 'asset_'.Str::random(8);

    Attribute::factory()->create(['code' => $code, 'type' => 'asset']);

    return $code;
}

it('resolves DAM webhook service over the base one when PublicUrl is absent', function () {
    expect(class_exists(PublicUrlServiceProvider::class))->toBeFalse();

    expect(app(BaseWebhookService::class))->toBeInstanceOf(DamWebhookService::class);
});

it('rewrites an added common asset id to its path in the webhook changes', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/red-shoe.jpg']);
    $code = damAssetAttributeCode();

    $changes = damNormalizeWebhookChanges([
        'added' => ['common' => [$code => (string) $asset->id]],
    ]);

    expect($changes['added']['common'][$code])->toBe($asset->path);
});

it('rewrites both sides of a changed asset value', function () {
    $old = Asset::factory()->create(['path' => 'assets/Root/old.jpg']);
    $new = Asset::factory()->create(['path' => 'assets/Root/new.jpg']);
    $code = damAssetAttributeCode();

    $changes = damNormalizeWebhookChanges([
        'changed' => ['common' => [$code => ['old' => (string) $old->id, 'new' => (string) $new->id]]],
    ]);

    expect($changes['changed']['common'][$code])->toBe([
        'old' => $old->path,
        'new' => $new->path,
    ]);
});

it('rewrites asset ids nested in the channel_locale_specific scope', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/deep.jpg']);
    $code = damAssetAttributeCode();

    $changes = damNormalizeWebhookChanges([
        'added' => [
            'channel_locale_specific' => [
                'default' => ['en_US' => [$code => (string) $asset->id]],
            ],
        ],
    ]);

    expect($changes['added']['channel_locale_specific']['default']['en_US'][$code])->toBe($asset->path);
});

it('joins multiple asset ids with a comma and keeps unknown ids', function () {
    $first = Asset::factory()->create(['path' => 'assets/Root/a.jpg']);
    $code = damAssetAttributeCode();

    $changes = damNormalizeWebhookChanges([
        'added' => ['common' => [$code => $first->id.',999999']],
    ]);

    expect($changes['added']['common'][$code])->toBe($first->path.', 999999');
});

it('leaves non-asset attribute changes untouched', function () {
    $code = damAssetAttributeCode();
    $textCode = 'text_'.Str::random(8);

    $changes = damNormalizeWebhookChanges([
        'changed' => [
            'common' => [$textCode => ['old' => 'A', 'new' => 'B']],
        ],
    ]);

    expect($changes['changed']['common'][$textCode])->toBe(['old' => 'A', 'new' => 'B']);
});
