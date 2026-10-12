<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Webkul\DAM\Http\Controllers\API\Asset\AssetController;

it('converts a downloaded remote file into a valid test upload', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    Http::fake([
        '93.184.216.34/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    $files = app(AssetController::class)->downloadAndConvertFiles(
        new Request(['files' => ['http://93.184.216.34/remote-logo.png']])
    );

    expect($files)->toBeArray()->toHaveCount(1);

    $file = $files[0];

    expect($file)->toBeInstanceOf(UploadedFile::class)
        ->and($file->getError())->toBe(UPLOAD_ERR_OK)
        ->and($file->isValid())->toBeTrue()
        ->and($file->getClientOriginalName())->toBe('remote-logo.png')
        ->and($file->getClientMimeType())->toBe('image/png');

    @unlink($file->getPathname());
});
