<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Presenters\Asset as AssetPresenter;
use Webkul\DAM\Services\AssetVersionService;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
    AssetPresenter::clearRequestCache();
});

it('returns an empty array for unrelated fields', function () {
    AssetPresenter::setAssetIdResolver(fn () => 1);

    $result = AssetPresenter::representValueForHistory('a', 'b', 'file_size');
    expect($result)->toBe([]);
});

it('returns an empty array when the asset id cannot be resolved', function () {
    AssetPresenter::setAssetIdResolver(fn () => null);

    $result = AssetPresenter::representValueForHistory('a', 'b', 'path');
    expect($result)->toBe([]);
});

it('renders thumbnail HTML on both old and new sides for path with eye preview only', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD');
    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => 'assets/Root/old.jpg',
    ]);
    app(AssetVersionService::class)->backup($asset);
    $asset->update(['file_name' => 'new.jpg', 'path' => 'assets/Root/new.jpg']);

    AssetPresenter::clearRequestCache();
    AssetPresenter::setAssetIdResolver(fn () => $asset->id);

    $result = AssetPresenter::representValueForHistory(
        'assets/Root/old.jpg',
        'assets/Root/new.jpg',
        'path'
    );

    expect($result)->toHaveKey('path');
    expect($result['path']['old'])->toContain('dam-hist-thumb');
    expect($result['path']['old'])->toContain('dam-hist-eye');
    expect($result['path']['new'])->toContain('dam-hist-thumb');
    expect($result['path']['new'])->toContain('dam-hist-eye');

    // OLD side now resolves by asset_id via the new DAM-owned endpoint.
    expect($result['path']['old'])->toContain('admin/dam/history/thumbnail/'.$asset->id);
    // NEW side keeps the existing file-thumbnail endpoint keyed by live path.
    expect($result['path']['new'])->toContain('admin/dam/file/thumbnail');
    expect($result['path']['new'])->toContain('new.jpg');

    // Restore is no longer rendered per-thumbnail — it lives in the modal footer.
    expect($result['path']['old'])->not->toContain('dam-hist-restore');
    expect($result['path']['new'])->not->toContain('dam-hist-restore');
});

it('renders a thumbnail image for non-image mime types with an icon fallback', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'doc.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'path'      => 'assets/Root/doc.pdf',
    ]);

    AssetPresenter::clearRequestCache();
    AssetPresenter::setAssetIdResolver(fn () => $asset->id);

    $result = AssetPresenter::representValueForHistory('a', 'b', 'path');

    // PDFs/videos use the thumbnail endpoint (which renders first-page / first-frame JPGs)
    // with a fallback icon embedded via data-fallback in case generation fails.
    expect($result['path']['new'])->toContain('<img');
    expect($result['path']['new'])->toContain('admin/dam/file/thumbnail');
    expect($result['path']['new'])->toContain('data-fallback');
});
