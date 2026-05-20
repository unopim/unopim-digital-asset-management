<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;
use Webkul\DAM\Repositories\ShareRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    DB::table('dam_shares')->delete();
    $this->repo = app(ShareRepository::class);
});

it('creates an asset share with a unique 40-char token', function () {
    $asset = Asset::factory()->create();

    $share = $this->repo->createForAsset($asset->id, now()->addDays(7), null);

    expect($share->token)->toHaveLength(40);
    expect($share->share_type)->toBe(Share::TYPE_ASSET);
    expect($share->target_id)->toBe($asset->id);
    expect($share->expires_at->isFuture())->toBeTrue();
    expect($share->revoked_at)->toBeNull();
});

it('creates a directory share', function () {
    $directory = Directory::factory()->create();

    $share = $this->repo->createForDirectory($directory->id, now()->addDays(7), null);

    expect($share->share_type)->toBe(Share::TYPE_DIRECTORY);
    expect($share->target_id)->toBe($directory->id);
});

it('creates a share with no expiry when expiresAt is null', function () {
    $asset = Asset::factory()->create();

    $share = $this->repo->createForAsset($asset->id, null, null);

    expect($share->expires_at)->toBeNull();
    expect($share->isActive())->toBeTrue();
});

it('returns the share for a valid active token', function () {
    $asset = Asset::factory()->create();
    $share = $this->repo->createForAsset($asset->id, now()->addDay(), null);

    $found = $this->repo->findActiveByToken($share->token);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($share->id);
});

it('does not return expired shares from findActiveByToken', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->expired()->create();

    $found = $this->repo->findActiveByToken($share->token);

    expect($found)->toBeNull();
});

it('does not return revoked shares from findActiveByToken', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->revoked()->create();

    $found = $this->repo->findActiveByToken($share->token);

    expect($found)->toBeNull();
});

it('returns null for an unknown token', function () {
    expect($this->repo->findActiveByToken('non-existent-token'))->toBeNull();
});

it('revokes an active share', function () {
    $asset = Asset::factory()->create();
    $share = $this->repo->createForAsset($asset->id, now()->addDays(7), null);

    $revoked = $this->repo->revoke($share->id);

    expect($revoked)->toBeTrue();
    expect($share->fresh()->revoked_at)->not->toBeNull();
});

it('returns false when revoking an already-revoked share', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->revoked()->create();

    expect($this->repo->revoke($share->id))->toBeFalse();
});

it('atomically increments view_count', function () {
    $asset = Asset::factory()->create();
    $share = $this->repo->createForAsset($asset->id, now()->addDay(), null);

    $this->repo->incrementView($share);
    $this->repo->incrementView($share);

    expect($share->fresh()->view_count)->toBe(2);
    expect($share->fresh()->last_accessed_at)->not->toBeNull();
});

it('atomically increments download_count', function () {
    $asset = Asset::factory()->create();
    $share = $this->repo->createForAsset($asset->id, now()->addDay(), null);

    $this->repo->incrementDownload($share);

    expect($share->fresh()->download_count)->toBe(1);
});

it('Share::active scope excludes expired and revoked rows', function () {
    $asset = Asset::factory()->create();
    $active = Share::factory()->forAsset($asset->id)->create();
    Share::factory()->forAsset($asset->id)->expired()->create();
    Share::factory()->forAsset($asset->id)->revoked()->create();

    $tokens = Share::query()->active()->pluck('token');

    expect($tokens)->toHaveCount(1);
    expect($tokens->first())->toBe($active->token);
});
