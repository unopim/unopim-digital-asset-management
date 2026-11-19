<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\Importers\Asset\Importer;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetIngestionService;
use Webkul\DAM\Services\IngestResult;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

function damPng(): string
{
    return base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
    );
}

function damAssetImporter(string $conflictStrategy, string $imagesDir, bool $autoTag = false): array
{
    $jobInstance = JobInstances::factory()->importJob()->create([
        'entity_type' => 'assets',
        'filters'     => ['conflict_strategy' => $conflictStrategy, 'auto_tag' => $autoTag],
    ]);

    $jobTrack = JobTrack::factory()->create([
        'action'                => Import::ACTION_APPEND,
        'job_instances_id'      => $jobInstance->id,
        'images_directory_path' => $imagesDir,
    ]);

    $importer = app(Importer::class);
    $importer->setImport($jobTrack);
    $importer->setErrorHelper(app(Error::class));

    return [$importer, $jobTrack];
}

function damBatch(JobTrack $jobTrack, array $rows): JobTrackBatch
{
    return JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => $rows,
    ]);
}

beforeEach(function () {
    $this->damResetAssetTables();
    Storage::fake('local');
    Storage::fake(Directory::getAssetDisk());
    Bus::fake();
});

it('creates assets from a server file and a public url, and errors a row with no source', function () {
    Storage::disk('local')->put('public/imp/assets/Root/Brand/a.png', damPng());
    Http::fake(['93.184.216.34/*' => Http::response(damPng(), 200)]);

    [$importer, $jobTrack] = damAssetImporter('skip', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'assets/Root/Brand/a.png', 'file_name' => 'a.png', 'directory' => 'Root/Brand', 'tags' => 'brand', 'properties' => ''],
        ['path' => 'http://93.184.216.34/b.png', 'file_name' => 'b.png', 'directory' => 'Root/Web', 'tags' => '', 'properties' => ''],
        ['path' => 'assets/Root/Missing/c.png', 'file_name' => 'c.png', 'directory' => 'Root/Missing', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(2)
        ->and(Asset::where('path', 'assets/Root/Brand/a.png')->exists())->toBeTrue()
        ->and(Asset::where('path', 'assets/Root/Web/b.png')->exists())->toBeTrue()
        ->and(Asset::where('path', 'assets/Root/Missing/c.png')->exists())->toBeFalse();

    Bus::assertDispatchedTimes(ProcessAssetUpload::class, 2);
});

it('skips a row whose asset already exists under the skip strategy', function () {
    Storage::disk('local')->put('public/imp/assets/Root/Brand/a.png', damPng());
    Asset::factory()->create(['path' => 'assets/Root/Brand/a.png', 'file_size' => 7]);

    [$importer, $jobTrack] = damAssetImporter('skip', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'assets/Root/Brand/a.png', 'file_name' => 'a.png', 'directory' => 'Root/Brand', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(0)
        ->and($importer->getUpdatedItemsCount())->toBe(0)
        ->and(Asset::where('path', 'assets/Root/Brand/a.png')->count())->toBe(1)
        ->and(Asset::find(Asset::where('path', 'assets/Root/Brand/a.png')->first()->id)->file_size)->toBe(7);

    Bus::assertNotDispatched(ProcessAssetUpload::class);
});

it('overwrites an existing asset under the overwrite strategy', function () {
    Storage::disk('local')->put('public/imp/assets/Root/Brand/a.png', damPng());
    Asset::factory()->create(['path' => 'assets/Root/Brand/a.png', 'file_size' => 1]);

    [$importer, $jobTrack] = damAssetImporter('overwrite', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'assets/Root/Brand/a.png', 'file_name' => 'a.png', 'directory' => 'Root/Brand', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    expect($importer->getUpdatedItemsCount())->toBe(1)
        ->and(Asset::where('path', 'assets/Root/Brand/a.png')->count())->toBe(1)
        ->and(Asset::where('path', 'assets/Root/Brand/a.png')->first()->file_size)->toBeGreaterThan(1);

    Bus::assertDispatched(ProcessAssetUpload::class);
});

it('errors a single row that throws unexpectedly and still processes the rest of the batch', function () {
    Storage::disk('local')->put('public/imp/assets/Root/A/boom.png', damPng());
    Storage::disk('local')->put('public/imp/assets/Root/A/ok.png', damPng());

    app()->instance(AssetIngestionService::class, new class extends AssetIngestionService
    {
        public function __construct() {}

        public function ingest(string $localPath, string $damPath, ?string $fileName, string $conflictStrategy, bool $autoTag, array $extraDirectoryNames = []): IngestResult
        {
            if (str_contains($damPath, 'boom')) {
                throw new RuntimeException('unexpected failure');
            }

            return new IngestResult('created', Asset::create([
                'path'      => $damPath,
                'file_name' => basename($damPath),
                'file_type' => 'image',
                'file_size' => 1,
                'mime_type' => 'image/png',
                'extension' => 'png',
            ]));
        }
    });

    [$importer, $jobTrack] = damAssetImporter('skip', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'assets/Root/A/boom.png', 'file_name' => 'boom.png', 'directory' => 'Root/A', 'tags' => '', 'properties' => ''],
        ['path' => 'assets/Root/A/ok.png', 'file_name' => 'ok.png', 'directory' => 'Root/A', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(1)
        ->and(Asset::where('path', 'assets/Root/A/ok.png')->exists())->toBeTrue()
        ->and(Asset::where('path', 'assets/Root/A/boom.png')->exists())->toBeFalse();
});

it('does not download the url when the asset exists and the strategy is skip', function () {
    Asset::factory()->create(['path' => 'assets/Root/Brand/a.png']);
    Http::fake(['93.184.216.34/*' => Http::response(damPng(), 200)]);

    [$importer, $jobTrack] = damAssetImporter('skip', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'http://93.184.216.34/a.png', 'file_name' => 'a.png', 'directory' => 'Root/Brand', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    Http::assertNothingSent();
    expect($importer->getCreatedItemsCount())->toBe(0)
        ->and($importer->getUpdatedItemsCount())->toBe(0);
});

it('errors a row whose directory contains traversal segments', function () {
    Storage::disk('local')->put('public/imp/x.png', damPng());

    [$importer, $jobTrack] = damAssetImporter('skip', 'imp');

    $batch = damBatch($jobTrack, [
        ['path' => 'x.png', 'file_name' => 'x.png', 'directory' => 'Root/..', 'tags' => '', 'properties' => ''],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(0)
        ->and(Asset::count())->toBe(0);

    Bus::assertNotDispatched(ProcessAssetUpload::class);
});
