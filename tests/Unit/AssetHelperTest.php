<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Directory;

it('should detect image file type from mime type', function () {
    $file = UploadedFile::fake()->image('test.jpg');
    expect(AssetHelper::getFileType($file))->toBe('image');
});

it('should detect video file type from mime type', function () {
    $file = UploadedFile::fake()->create('test.mp4', 100, 'video/mp4');
    expect(AssetHelper::getFileType($file))->toBe('video');
});

it('should detect audio file type from mime type', function () {
    $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');
    expect(AssetHelper::getFileType($file))->toBe('audio');
});

it('should detect document file type from mime type', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
    expect(AssetHelper::getFileType($file))->toBe('document');
});

it('should detect image type using extension', function () {
    expect(AssetHelper::getFileTypeUsingExtension('jpg'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('jpeg'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('png'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('gif'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('svg'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('bmp'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('webp'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('tiff'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('tif'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('jfif'))->toBe('image');
});

it('should detect video type using extension', function () {
    expect(AssetHelper::getFileTypeUsingExtension('mp4'))->toBe('video');
    expect(AssetHelper::getFileTypeUsingExtension('mkv'))->toBe('video');
    expect(AssetHelper::getFileTypeUsingExtension('avi'))->toBe('video');
    expect(AssetHelper::getFileTypeUsingExtension('mov'))->toBe('video');
    expect(AssetHelper::getFileTypeUsingExtension('flv'))->toBe('video');
});

it('should detect audio type using extension', function () {
    expect(AssetHelper::getFileTypeUsingExtension('mp3'))->toBe('audio');
    expect(AssetHelper::getFileTypeUsingExtension('wav'))->toBe('audio');
    expect(AssetHelper::getFileTypeUsingExtension('aac'))->toBe('audio');
    expect(AssetHelper::getFileTypeUsingExtension('flac'))->toBe('audio');
});

it('should detect sheet type using extension', function () {
    expect(AssetHelper::getFileTypeUsingExtension('xls'))->toBe('sheet');
    expect(AssetHelper::getFileTypeUsingExtension('xlsx'))->toBe('sheet');
    expect(AssetHelper::getFileTypeUsingExtension('csv'))->toBe('sheet');
    expect(AssetHelper::getFileTypeUsingExtension('ods'))->toBe('sheet');
});

it('should detect file type using extension for documents', function () {
    expect(AssetHelper::getFileTypeUsingExtension('pdf'))->toBe('file');
    expect(AssetHelper::getFileTypeUsingExtension('doc'))->toBe('file');
    expect(AssetHelper::getFileTypeUsingExtension('docx'))->toBe('file');
    expect(AssetHelper::getFileTypeUsingExtension('txt'))->toBe('file');
    expect(AssetHelper::getFileTypeUsingExtension('rtf'))->toBe('file');
    expect(AssetHelper::getFileTypeUsingExtension('odt'))->toBe('file');
});

it('should return unspecified for unknown extensions', function () {
    expect(AssetHelper::getFileTypeUsingExtension('xyz'))->toBe('unspecified');
    expect(AssetHelper::getFileTypeUsingExtension('bin'))->toBe('unspecified');
    expect(AssetHelper::getFileTypeUsingExtension('dat'))->toBe('unspecified');
});

it('should handle case insensitive extension detection', function () {
    expect(AssetHelper::getFileTypeUsingExtension('JPG'))->toBe('image');
    expect(AssetHelper::getFileTypeUsingExtension('MP4'))->toBe('video');
    expect(AssetHelper::getFileTypeUsingExtension('PDF'))->toBe('file');
});

it('should truncate long file names with ellipsis', function () {
    $longName = 'this-is-a-very-long-file-name-that-exceeds-limit.png';
    $result = AssetHelper::getDisplayFileName($longName);

    expect(strlen($result))->toBeLessThanOrEqual(30);
    expect($result)->toContain('...');
    expect($result)->toEndWith('.png');
});

it('should not truncate short file names', function () {
    $shortName = 'short.png';
    expect(AssetHelper::getDisplayFileName($shortName))->toBe('short.png');
});

it('should not truncate file names at exactly 29 characters', function () {
    $exactName = str_repeat('a', 25).'.png';
    expect(AssetHelper::getDisplayFileName($exactName))->toBe($exactName);
});

it('should identify forbidden file extensions', function () {
    expect(AssetHelper::isForbiddenFile('php', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('js', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('py', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('sh', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('bat', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('exe', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('pl', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('cgi', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('asp', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('aspx', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('jsp', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('rb', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('jar', null))->toBeTrue();
});

it('should identify forbidden mime types', function () {
    expect(AssetHelper::isForbiddenFile(null, 'application/x-php'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'application/x-javascript'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'text/javascript'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'application/javascript'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'text/x-python'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, 'application/x-msdownload'))->toBeTrue();
});

it('should allow safe file extensions', function () {
    expect(AssetHelper::isForbiddenFile('jpg', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('png', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('pdf', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('mp4', null))->toBeFalse();
    expect(AssetHelper::isForbiddenFile('doc', null))->toBeFalse();
});

it('should allow safe mime types', function () {
    expect(AssetHelper::isForbiddenFile(null, 'image/jpeg'))->toBeFalse();
    expect(AssetHelper::isForbiddenFile(null, 'application/pdf'))->toBeFalse();
    expect(AssetHelper::isForbiddenFile(null, 'video/mp4'))->toBeFalse();
});

it('should handle case insensitive extension check for forbidden files', function () {
    expect(AssetHelper::isForbiddenFile('PHP', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('Js', null))->toBeTrue();
    expect(AssetHelper::isForbiddenFile('EXE', null))->toBeTrue();
});

it('should return false when both extension and mime are null', function () {
    expect(AssetHelper::isForbiddenFile(null, null))->toBeFalse();
});

it('should return correct s3 url based on visibility for pdf files', function (string $visibility, string $expectedMethod) {
    config(['filesystems.default' => 's3']);

    $path = 'assets/Root/test.pdf';
    $expectedUrl = "https://s3.example.com/{$path}".($visibility === 'private' ? '?signature=test' : '');

    $disk = Mockery::mock();
    $disk->shouldReceive('exists')
        ->once()
        ->with($path)
        ->andReturn(true);
    $disk->shouldReceive('mimeType')
        ->once()
        ->with($path)
        ->andReturn('application/pdf');
    $disk->shouldReceive('getVisibility')
        ->once()
        ->with($path)
        ->andReturn($visibility);
    $disk->shouldReceive($expectedMethod)
        ->once()
        ->with($path, ...($visibility === 'private' ? [Mockery::type(Carbon::class)] : []))
        ->andReturn($expectedUrl);

    Storage::shouldReceive('disk')
        ->once()
        ->with(Directory::ASSETS_DISK_AWS)
        ->andReturn($disk);

    expect(AssetHelper::getPreviewUrl($path, 1356))
        ->toBe($expectedUrl);
})->with([
    'private visibility returns signed url' => ['private', 'temporaryUrl'],
    'public visibility returns direct url'  => ['public',  'url'],
]);

it('should keep using the preview route on local storage', function () {
    config(['filesystems.default' => 'local']);

    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
    $path = 'assets/Root/'.$file->getClientOriginalName();
    $assetId = 1356;
    $encodedPath = urlencode(urlencode($path));

    $previewUrl = AssetHelper::getPreviewUrl($path, $assetId);

    expect($previewUrl)
        ->toContain(route('admin.dam.file.preview', [], false))
        ->toContain("path={$encodedPath}");
});

it('should keep using the preview route for resizable images on s3', function () {
    config(['filesystems.default' => 's3']);

    $file = UploadedFile::fake()->image('test.png');
    $path = 'assets/Root/'.$file->getClientOriginalName();
    $assetId = 1356;
    $encodedPath = urlencode(urlencode($path));

    $disk = Mockery::mock();
    $disk->shouldReceive('exists')
        ->once()
        ->with($path)
        ->andReturn(true);
    $disk->shouldReceive('mimeType')
        ->once()
        ->with($path)
        ->andReturn($file->getMimeType());

    Storage::shouldReceive('disk')
        ->once()
        ->with(Directory::ASSETS_DISK_AWS)
        ->andReturn($disk);

    $previewUrl = AssetHelper::getPreviewUrl($path, $assetId);

    expect($previewUrl)
        ->toContain(route('admin.dam.file.preview', [], false))
        ->toContain("path={$encodedPath}");
});
