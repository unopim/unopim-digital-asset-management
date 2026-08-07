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

it('should block OS metadata files by filename', function () {
    expect(AssetHelper::isForbiddenFile(null, null, '.DS_Store'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, null, '._.DS_Store'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, null, 'Thumbs.db'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, null, 'desktop.ini'))->toBeTrue();
    expect(AssetHelper::isForbiddenFile(null, null, 'photo.png'))->toBeFalse();
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

it('rejects a forbidden extension hidden behind an allowed one', function (string $fileName, string $extension, string $mimeType) {
    expect(AssetHelper::isForbiddenFile($extension, $mimeType, $fileName))->toBeTrue();
})->with([
    'php masked as jpg'      => ['test.php.jpg', 'jpg', 'image/jpeg'],
    'numbered php handler'   => ['shell.php5.png', 'png', 'image/png'],
    'phtml masked as webp'   => ['evil.phtml.webp', 'webp', 'image/webp'],
    'trailing dot and space' => ['shell.php. ', 'php', 'text/x-php'],
    'html masked as gif'     => ['page.html.gif', 'gif', 'image/gif'],
    'executable masked'      => ['setup.exe.png', 'png', 'image/png'],
]);

it('rejects archive uploads', function (string $fileName, string $extension, string $mimeType) {
    expect(AssetHelper::isForbiddenFile($extension, $mimeType, $fileName))->toBeTrue();
})->with([
    'zip'    => ['bundle.zip', 'zip', 'application/zip'],
    'rar'    => ['bundle.rar', 'rar', 'application/vnd.rar'],
    'seven'  => ['bundle.7z', '7z', 'application/x-7z-compressed'],
    'tar gz' => ['backup.tar.gz', 'gz', 'application/gzip'],
]);

it('still permits legitimate asset uploads', function (string $fileName, string $extension, string $mimeType) {
    expect(AssetHelper::isForbiddenFile($extension, $mimeType, $fileName))->toBeFalse();
})->with([
    'webp photo'      => ['apparel-linen-shirt.webp', 'webp', 'image/webp'],
    'jpg photo'       => ['photo.jpg', 'jpg', 'image/jpeg'],
    'dotted mp4'      => ['campaign.autumn.v2.mp4', 'mp4', 'video/mp4'],
    'podcast mp3'     => ['podcast-ep01.mp3', 'mp3', 'audio/mpeg'],
    'pdf datasheet'   => ['datasheet-linen-shirt.pdf', 'pdf', 'application/pdf'],
    'xlsx price list' => ['price-list-2026-audio.xlsx', 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'svg logo'        => ['meridian-logo.svg', 'svg', 'image/svg+xml'],
]);

/**
 * A backend script renamed to a media extension carries one plausible extension and
 * nothing else suspicious in its name, so only its bytes give it away.
 */
it('rejects a script renamed to a media extension', function (string $fileName, string $content) {
    $path = tempnam(sys_get_temp_dir(), 'damsec_');
    file_put_contents($path, $content);

    try {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        expect(AssetHelper::isForbiddenFile($extension, mime_content_type($path), $fileName, $path))->toBeTrue();
    } finally {
        @unlink($path);
    }
})->with([
    'php renamed to jpg'   => ['adminer.jpg', "<?php\neval(\$_REQUEST['x']);\n"],
    'shell renamed to png' => ['evil.png', "#!/bin/bash\nrm -rf /\n"],
    'gif php polyglot'     => ['polyglot.gif', "GIF89a<?php system(\$_GET['c']); ?>"],
    'php renamed to mp4'   => ['clip.mp4', "<?php phpinfo();\n"],
    'script in svg'        => ['x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
]);

it('accepts genuine media from the demo library', function (string $relativePath) {
    $path = __DIR__.'/../../src/Database/Data/demo-assets/Root/'.$relativePath;

    expect(is_file($path))->toBeTrue();

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    expect(AssetHelper::isForbiddenFile($extension, mime_content_type($path), basename($path), $path))->toBeFalse();
})->with([
    'webp photo'  => ['Product Photography/Apparel/apparel-linen-shirt.webp'],
    'jpg photo'   => ['Product Photography/Apparel/apparel-tailored-chinos.jpg'],
    'png logo'    => ['Brand/Logos/meridian-logo-primary.png'],
    'svg logo'    => ['Brand/Logos/meridian-logo.svg'],
    'mp4 video'   => ['Marketing/Campaign Videos/campaign-audio-launch.mp4'],
    'webm video'  => ['Marketing/Social Clips/social-loop-outdoor.webm'],
    'mp3 audio'   => ['Audio/Podcasts/podcast-ep01-designing-the-catalog.mp3'],
    'wav audio'   => ['Audio/Sound Logos/sound-logo-primary.wav'],
    'pdf doc'     => ['Documents/Datasheets/datasheet-linen-shirt.pdf'],
    'docx doc'    => ['Documents/Contracts/contract-supplier-agreement.docx'],
    'xlsx sheet'  => ['Documents/Price Lists/price-list-2026-audio.xlsx'],
    'csv list'    => ['Documents/Price Lists/price-list-2026-outdoor.csv'],
]);
