<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Http\Controllers\FileController;
use Webkul\DAM\Models\Directory;

it('rejects HTML and other executable/markup uploads by extension', function (string $extension) {
    expect(AssetHelper::isForbiddenFile($extension, null, "evil.{$extension}", null))->toBeTrue();
})->with(['html', 'htm', 'xhtml', 'shtml', 'hta', 'php', 'phtml', 'phar']);

it('rejects HTML uploads by MIME type even with an innocent extension', function () {
    expect(AssetHelper::isForbiddenFile('txt', 'text/html', 'evil.txt', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'application/xhtml+xml', 'evil', null))->toBeTrue();
});

it('still allows genuine media uploads', function () {
    expect(AssetHelper::isForbiddenFile('png', 'image/png', 'ok.png', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('mp4', 'video/mp4', 'ok.mp4', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('pdf', 'application/pdf', 'ok.pdf', null))->toBeFalse();
});

it('treats only real media MIME types as inline-safe', function () {
    expect(AssetHelper::isInlineSafeMime('image/png'))->toBeTrue();
    expect(AssetHelper::isInlineSafeMime('video/mp4'))->toBeTrue();
    expect(AssetHelper::isInlineSafeMime('audio/mpeg'))->toBeTrue();
    expect(AssetHelper::isInlineSafeMime('application/pdf'))->toBeTrue();
    expect(AssetHelper::isInlineSafeMime('image/svg+xml'))->toBeTrue();

    expect(AssetHelper::isInlineSafeMime('text/html'))->toBeFalse();
    expect(AssetHelper::isInlineSafeMime('application/xml'))->toBeFalse();
    expect(AssetHelper::isInlineSafeMime('text/plain'))->toBeFalse();
    expect(AssetHelper::isInlineSafeMime(''))->toBeFalse();
    expect(AssetHelper::isInlineSafeMime(null))->toBeFalse();
});

it('exposes hardening headers that block script execution and MIME sniffing', function () {
    $headers = AssetHelper::assetResponseHeaders();

    expect($headers['X-Content-Type-Options'])->toBe('nosniff');
    expect($headers['Content-Security-Policy'])->toContain("default-src 'none'");
});

it('fetchFile serves an HTML file as an attachment with hardening headers', function () {
    config(['filesystems.default' => Directory::ASSETS_DISK_PRIVATE]);
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/evil.html';
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($path, '<script>alert(document.cookie)</script>');

    $response = (new FileController)->fetchFile($path);

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
});

it('fetchFile serves a genuine image inline (no forced download)', function () {
    config(['filesystems.default' => Directory::ASSETS_DISK_PRIVATE]);
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/photo.jpg';
    $image = UploadedFile::fake()->image('photo.jpg', 8, 8);
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($path, file_get_contents($image->getRealPath()));

    $response = (new FileController)->fetchFile($path);

    expect($response->headers->get('Content-Disposition'))->toBeNull();
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});
