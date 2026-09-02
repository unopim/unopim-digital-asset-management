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

it('treats real media MIME types as inline-safe, trusting the upload-time content scan for PDF/SVG', function () {
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

it('rejects a PDF carrying auto-run scripting', function (string $marker) {
    $path = tempnam(sys_get_temp_dir(), 'dam_pdf_test_');
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog {$marker} >>\nendobj\n%%EOF");

    expect(AssetHelper::isForbiddenFile('pdf', 'application/pdf', 'evil.pdf', $path))->toBeTrue();

    unlink($path);
})->with([
    '/OpenAction 2 0 R',
    '/AA << /O 2 0 R >>',
    '/Names << /JavaScript 2 0 R >>',
    '/Launch (calc.exe)',
]);

it('still allows a benign PDF with no scripting markers', function () {
    $path = tempnam(sys_get_temp_dir(), 'dam_pdf_test_');
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");

    expect(AssetHelper::isForbiddenFile('pdf', 'application/pdf', 'ok.pdf', $path))->toBeFalse();

    unlink($path);
});

it('rejects an SVG carrying inline script or event-handler payloads', function (string $svg) {
    $path = tempnam(sys_get_temp_dir(), 'dam_svg_test_');
    file_put_contents($path, $svg);

    expect(AssetHelper::isForbiddenFile('svg', 'image/svg+xml', 'evil.svg', $path))->toBeTrue();

    unlink($path);
})->with([
    '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
    '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>',
    '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)">x</a></svg>',
    '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><body xmlns="http://www.w3.org/1999/xhtml">x</body></foreignObject></svg>',
]);

it('still allows a benign SVG with no script or event handlers', function () {
    $path = tempnam(sys_get_temp_dir(), 'dam_svg_test_');
    file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4"/></svg>');

    expect(AssetHelper::isForbiddenFile('svg', 'image/svg+xml', 'ok.svg', $path))->toBeFalse();

    unlink($path);
});

it('rejects a malicious SVG even when the extension/MIME are spoofed as an unrelated image type', function () {
    $path = tempnam(sys_get_temp_dir(), 'dam_svg_spoof_test_');
    file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');

    expect(AssetHelper::isForbiddenFile('png', 'image/png', 'evil.png', $path))->toBeTrue();

    unlink($path);
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

it('fetchFile serves a clean PDF inline, relying on the upload-time scan to keep it clean', function () {
    config(['filesystems.default' => Directory::ASSETS_DISK_PRIVATE]);
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    Auth::shouldReceive('check')->andReturn(true);

    $path = 'assets/Root/report.pdf';
    Storage::disk(Directory::ASSETS_DISK_PRIVATE)->put($path, "%PDF-1.4\n%%EOF");

    $response = (new FileController)->fetchFile($path);

    expect($response->headers->get('Content-Disposition'))->toBeNull();
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
