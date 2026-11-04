<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetIngestionService;

function damPngFixture(): string
{
    $bytes = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
    );

    $path = tempnam(sys_get_temp_dir(), 'dam-ingest-').'.png';
    file_put_contents($path, $bytes);

    return $path;
}

beforeEach(function () {
    $this->damResetAssetTables();
    Storage::fake(Directory::getAssetDisk());
    Bus::fake();

    $this->service = app(AssetIngestionService::class);
});

it('creates a new asset, builds its directory, and queues finalisation', function () {
    $result = $this->service->ingest(
        localPath: damPngFixture(),
        damPath: 'assets/Root/Brand/logo.png',
        fileName: null,
        conflictStrategy: 'skip',
        autoTag: false,
    );

    expect($result->status)->toBe('created')
        ->and($result->asset)->not->toBeNull()
        ->and($result->asset->path)->toBe('assets/Root/Brand/logo.png')
        ->and($result->asset->file_type)->toBe('image');

    $deepest = Directory::where('name', 'Brand')->first();
    expect($deepest)->not->toBeNull()
        ->and($result->asset->directories()->where('dam_directories.id', $deepest->id)->exists())->toBeTrue();

    Storage::disk(Directory::getAssetDisk())->assertExists('assets/Root/Brand/logo.png');
    Bus::assertDispatched(ProcessAssetUpload::class);
});

it('skips a row whose destination path already has an asset', function () {
    Asset::factory()->create(['path' => 'assets/Root/Brand/logo.png']);

    $result = $this->service->ingest(
        localPath: damPngFixture(),
        damPath: 'assets/Root/Brand/logo.png',
        fileName: null,
        conflictStrategy: 'skip',
        autoTag: false,
    );

    expect($result->status)->toBe('skipped')
        ->and(Asset::where('path', 'assets/Root/Brand/logo.png')->count())->toBe(1);

    Bus::assertNotDispatched(ProcessAssetUpload::class);
});

it('overwrites the existing asset in place under the overwrite strategy', function () {
    $existing = Asset::factory()->create([
        'path'      => 'assets/Root/Brand/logo.png',
        'file_size' => 1,
    ]);

    $result = $this->service->ingest(
        localPath: damPngFixture(),
        damPath: 'assets/Root/Brand/logo.png',
        fileName: null,
        conflictStrategy: 'overwrite',
        autoTag: false,
    );

    expect($result->status)->toBe('updated')
        ->and($result->asset->id)->toBe($existing->id)
        ->and(Asset::where('path', 'assets/Root/Brand/logo.png')->count())->toBe(1)
        ->and($result->asset->fresh()->file_size)->toBeGreaterThan(1);

    Bus::assertDispatched(ProcessAssetUpload::class);
});

it('leaves the binary untouched under the update_metadata strategy', function () {
    $existing = Asset::factory()->create([
        'path'      => 'assets/Root/Brand/logo.png',
        'file_size' => 42,
    ]);

    $result = $this->service->ingest(
        localPath: damPngFixture(),
        damPath: 'assets/Root/Brand/logo.png',
        fileName: null,
        conflictStrategy: 'update_metadata',
        autoTag: false,
    );

    expect($result->status)->toBe('updated')
        ->and($result->asset->fresh()->file_size)->toBe(42);

    Bus::assertNotDispatched(ProcessAssetUpload::class);
});
