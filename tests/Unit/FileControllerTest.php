<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\DAM\Http\Controllers\FileController;
use Webkul\DAM\Models\Directory;

// ─── Local disk: fix(dam) — getFileResponse() now uses response()->file() ─────
//
// Before: Storage::get() + response($file, 200)->header('Content-Type', $mime)
// After:  Storage::path() + response()->file($absolutePath) → BinaryFileResponse

it('getFileResponse on local disk returns BinaryFileResponse for cached thumbnail', function () {
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/photo.jpg';
    $thumbPath = 'thumbnails/'.$path;
    $fakeImage = UploadedFile::fake()->image('photo.jpg', 50, 50);
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($thumbPath, file_get_contents($fakeImage->getRealPath()));

    request()->merge(['path' => $path]);

    $response = (new FileController)->thumbnail();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('getFileResponse on local disk returns BinaryFileResponse for cached preview', function () {
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/photo.jpg';
    $size = 800;
    $previewPath = "preview/{$size}/{$path}";
    $fakeImage = UploadedFile::fake()->image('photo.jpg', 50, 50);
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($previewPath, file_get_contents($fakeImage->getRealPath()));

    request()->merge(['path' => $path, 'size' => (string) $size]);

    $response = (new FileController)->preview();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('getFileResponse on local disk returns BinaryFileResponse for SVG thumbnail', function () {
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/icon.svg';
    $thumbPath = 'thumbnails/'.$path;
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($thumbPath, '<svg xmlns="http://www.w3.org/2000/svg"/>');

    request()->merge(['path' => $path]);

    $response = (new FileController)->thumbnail();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('getFileResponse on local disk returns BinaryFileResponse for PDF preview via supported-media path', function () {
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/document.pdf';
    $fakePdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($path, file_get_contents($fakePdf->getRealPath()));

    // No pre-cached preview → preview() falls through to supported-media branch
    request()->merge(['path' => $path, 'size' => '800']);

    $response = (new FileController)->preview();

    // BinaryFileResponse because local disk uses response()->file()
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

// ─── AWS disk: redirect behaviour (unchanged code, important regression guard) ─

it('getFileResponse on AWS public disk redirects to direct S3 URL', function () {
    config(['filesystems.default' => 's3']);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/photo.jpg';
    $thumbPath = 'thumbnails/'.$path;
    $s3Url = 'https://s3.example.com/'.$thumbPath;

    $disk = Mockery::mock();
    $disk->shouldReceive('exists')->with($thumbPath)->andReturn(true);
    $disk->shouldReceive('mimeType')->with($thumbPath)->andReturn('image/jpeg');
    $disk->shouldReceive('getVisibility')->with($thumbPath)->andReturn('public');
    $disk->shouldReceive('url')->with($thumbPath)->andReturn($s3Url);

    Storage::shouldReceive('disk')->with(Directory::ASSETS_DISK_AWS)->andReturn($disk);

    request()->merge(['path' => $path]);

    $response = (new FileController)->thumbnail();

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe($s3Url);
});

it('getFileResponse on AWS private disk redirects to signed S3 URL', function () {
    config(['filesystems.default' => 's3']);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/photo.jpg';
    $thumbPath = 'thumbnails/'.$path;
    $signedUrl = 'https://s3.example.com/'.$thumbPath.'?X-Amz-Signature=abc';

    $disk = Mockery::mock();
    $disk->shouldReceive('exists')->with($thumbPath)->andReturn(true);
    $disk->shouldReceive('mimeType')->with($thumbPath)->andReturn('image/jpeg');
    $disk->shouldReceive('getVisibility')->with($thumbPath)->andReturn('private');
    $disk->shouldReceive('temporaryUrl')->with($thumbPath, Mockery::any())->andReturn($signedUrl);

    Storage::shouldReceive('disk')->with(Directory::ASSETS_DISK_AWS)->andReturn($disk);

    request()->merge(['path' => $path]);

    $response = (new FileController)->thumbnail();

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe($signedUrl);
});

// ─── Auth guard ───────────────────────────────────────────────────────────────

it('thumbnail aborts with 403 when user is not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);

    request()->merge(['path' => 'assets/Root/photo.jpg']);

    expect(fn () => (new FileController)->thumbnail())->toThrow(HttpException::class);
});

it('preview aborts with 403 when user is not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);

    request()->merge(['path' => 'assets/Root/photo.jpg', 'size' => '800']);

    expect(fn () => (new FileController)->preview())->toThrow(HttpException::class);
});
