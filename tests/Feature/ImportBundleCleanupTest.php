<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\Import;
use Webkul\DAM\Support\AssetBundleReader;
use Webkul\DataTransfer\Models\JobTrack;

beforeEach(function (): void {
    Storage::fake('private');
    Storage::fake('public');

    $this->workspace = function (int $trackId): array {
        $extracted = AssetBundleReader::EXTRACT_DIRECTORY_PREFIX.$trackId.'/products.csv';
        $staged = AssetBundleReader::MEDIA_DIRECTORY_PREFIX.$trackId.'/hero.jpg';

        Storage::disk('private')->put($extracted, "sku\nsku-1");
        Storage::disk('public')->put($staged, 'hero-bytes');

        return [$extracted, $staged];
    };
});

it('discards the extracted bundle and its staged media once the run completes', function () {
    $track = JobTrack::factory()->create();

    [$extracted, $staged] = ($this->workspace)($track->id);

    app(Import::class)->setImport($track)->completed();

    expect(Storage::disk('private')->exists($extracted))->toBeFalse()
        ->and(Storage::disk('public')->exists($staged))->toBeFalse();
});

it('discards the extracted bundle and its staged media when the run is cancelled', function () {
    $track = JobTrack::factory()->create();

    [$extracted, $staged] = ($this->workspace)($track->id);

    app(Import::class)->setImport($track)->cancel();

    expect(Storage::disk('private')->exists($extracted))->toBeFalse()
        ->and(Storage::disk('public')->exists($staged))->toBeFalse();
});

it('leaves another run\'s workspace untouched', function () {
    $track = JobTrack::factory()->create();
    $other = JobTrack::factory()->create();

    ($this->workspace)($track->id);

    [$extracted, $staged] = ($this->workspace)($other->id);

    app(Import::class)->setImport($track)->completed();

    expect(Storage::disk('private')->exists($extracted))->toBeTrue()
        ->and(Storage::disk('public')->exists($staged))->toBeTrue();
});

it('completes a run that never unpacked a bundle', function () {
    $track = JobTrack::factory()->create();

    Storage::disk('public')->put('import-images/catalogue/hero.jpg', 'hero-bytes');

    app(Import::class)->setImport($track)->completed();

    expect(Storage::disk('public')->exists('import-images/catalogue/hero.jpg'))->toBeTrue();
});
