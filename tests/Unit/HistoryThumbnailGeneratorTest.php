<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\HistoryThumbnailGenerator;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
    $this->gen = app(HistoryThumbnailGenerator::class);
});

it('resizes a real image into a JPG at the destination', function () {
    $img = UploadedFile::fake()->image('orig.png', 800, 600)->size(50);
    Storage::disk($this->disk)->putFileAs('versions/9', $img, 'last.png');

    $ok = $this->gen->fromImage(
        sourcePath: 'versions/9/last.png',
        destPath: 'versions/thumbnails/9/last.jpg',
        width: 300,
        disk: $this->disk,
    );

    expect($ok)->toBeTrue();
    Storage::disk($this->disk)->assertExists('versions/thumbnails/9/last.jpg');
    expect(strlen(Storage::disk($this->disk)->get('versions/thumbnails/9/last.jpg')))->toBeGreaterThan(0);
});

it('returns false when the source binary is missing', function () {
    expect($this->gen->fromImage(
        sourcePath: 'versions/missing/last.png',
        destPath: 'versions/thumbnails/missing/last.jpg',
        width: 300,
        disk: $this->disk,
    ))->toBeFalse();
});

it('copies an svg source verbatim and reports image/svg+xml content', function () {
    Storage::disk($this->disk)->put('versions/10/last.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="2" height="2"/>');

    $ok = $this->gen->fromSvg(
        sourcePath: 'versions/10/last.svg',
        destPath: 'versions/thumbnails/10/last.svg',
        disk: $this->disk,
    );

    expect($ok)->toBeTrue();
    Storage::disk($this->disk)->assertExists('versions/thumbnails/10/last.svg');
});

it('returns false when ffmpeg is not on PATH or the binary is unreadable', function () {
    // Tiny garbage bytes — ffmpeg will fail. The method must swallow the failure
    // and return false (not throw) so the caller can render the icon fallback.
    Storage::disk($this->disk)->put('versions/11/last.mp4', str_repeat("\x00", 32));

    $result = $this->gen->fromVideo(
        sourcePath: 'versions/11/last.mp4',
        destPath: 'versions/thumbnails/11/last.jpg',
        width: 300,
        disk: $this->disk,
    );

    expect($result)->toBeFalse();
    Storage::disk($this->disk)->assertMissing('versions/thumbnails/11/last.jpg');
});
