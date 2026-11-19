<?php

use Illuminate\Support\Facades\Http;
use Webkul\DAM\Exceptions\BlockedMediaHostException;
use Webkul\DAM\Exceptions\InvalidMediaUrlException;
use Webkul\DAM\Exceptions\MediaTooLargeException;
use Webkul\DAM\Exceptions\UnreachableMediaException;
use Webkul\DAM\Services\RemoteMediaDownloader;

beforeEach(function () {
    $this->downloader = app(RemoteMediaDownloader::class);
});

it('downloads a public url to a readable temp file', function () {
    Http::fake(['93.184.216.34/*' => Http::response('PNGBYTES', 200)]);

    $path = $this->downloader->download('http://93.184.216.34/logo.png', 10_000);

    expect(is_file($path))->toBeTrue()
        ->and(file_get_contents($path))->toBe('PNGBYTES');

    @unlink($path);
});

it('rejects a non-http scheme without making a request', function () {
    Http::fake();

    expect(fn () => $this->downloader->download('file:///etc/passwd', 10_000))
        ->toThrow(InvalidMediaUrlException::class);

    Http::assertNothingSent();
});

it('aborts when the body exceeds the max size', function () {
    Http::fake(['93.184.216.34/*' => Http::response(str_repeat('x', 5000), 200)]);

    expect(fn () => $this->downloader->download('http://93.184.216.34/big.png', 1000))
        ->toThrow(MediaTooLargeException::class);
});

it('fails on a non-2xx response', function () {
    Http::fake(['93.184.216.34/*' => Http::response('nope', 500)]);

    expect(fn () => $this->downloader->download('http://93.184.216.34/x.png', 10_000))
        ->toThrow(UnreachableMediaException::class);
});

it('blocks urls whose host resolves to a private or reserved range', function () {
    Http::fake();

    foreach ([
        'http://127.0.0.1/x.png',
        'http://10.0.0.1/x.png',
        'http://192.168.1.1/x.png',
        'http://169.254.169.254/latest/meta-data',
        'http://localhost/x.png',
    ] as $url) {
        expect(fn () => $this->downloader->download($url, 10_000))
            ->toThrow(BlockedMediaHostException::class);
    }

    Http::assertNothingSent();
});

it('blocks a redirect hop that points at an internal host', function () {
    Http::fake([
        '93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/evil.png']),
    ]);

    expect(fn () => $this->downloader->download('http://93.184.216.34/start.png', 10_000))
        ->toThrow(BlockedMediaHostException::class);
});
