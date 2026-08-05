<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Support\AssetBundleWriter;

/*
 * Asset binaries must reach the disk the download archive is built from, and an asset
 * shared by many rows must be streamed once rather than once per reference.
 */

beforeEach(function (): void {
    Storage::fake('private');

    $this->writer = new AssetBundleWriter;
});

it('streams an asset onto the export disk', function () {
    Storage::disk('private')->put('assets/Root/hero.jpg', 'binary');

    $wrote = $this->writer->write('assets/Root/hero.jpg', 'exports/7/uno-pim/assets/Root/hero.jpg');

    expect($wrote)->toBeTrue();

    Storage::disk('private')->assertExists('exports/7/uno-pim/assets/Root/hero.jpg');

    expect(Storage::disk('private')->get('exports/7/uno-pim/assets/Root/hero.jpg'))->toBe('binary');
});

it('writes a shared asset only once within a batch', function () {
    Storage::disk('private')->put('assets/Root/hero.jpg', 'binary');

    $destination = 'exports/7/uno-pim/assets/Root/hero.jpg';

    expect($this->writer->write('assets/Root/hero.jpg', $destination))->toBeTrue()
        ->and($this->writer->write('assets/Root/hero.jpg', $destination))->toBeFalse()
        ->and($this->writer->write('assets/Root/hero.jpg', $destination))->toBeFalse();
});

it('skips an asset a sibling batch already copied', function () {
    Storage::disk('private')->put('assets/Root/hero.jpg', 'binary');
    Storage::disk('private')->put('exports/7/uno-pim/assets/Root/hero.jpg', 'written-earlier');

    expect((new AssetBundleWriter)->write('assets/Root/hero.jpg', 'exports/7/uno-pim/assets/Root/hero.jpg'))
        ->toBeFalse()
        ->and(Storage::disk('private')->get('exports/7/uno-pim/assets/Root/hero.jpg'))
        ->toBe('written-earlier');
});

it('ignores an asset whose binary is missing', function () {
    expect($this->writer->write('assets/Root/gone.jpg', 'exports/7/uno-pim/assets/Root/gone.jpg'))
        ->toBeFalse();

    Storage::disk('private')->assertMissing('exports/7/uno-pim/assets/Root/gone.jpg');
});

it('targets the disk the export archive is assembled from', function () {
    expect(AssetBundleWriter::EXPORT_DISK)->toBe('private');
});
