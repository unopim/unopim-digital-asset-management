<?php

use Webkul\DAM\Support\SafeZipExtractor;

/*
 * The extractor is the only thing standing between an uploaded archive and the
 * filesystem, so each guard is asserted against an archive built to defeat it.
 */

beforeEach(function (): void {
    $this->workDir = sys_get_temp_dir().'/dam-safe-zip-'.getmypid().'-'.uniqid();

    mkdir($this->workDir.'/out', 0755, true);
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->workDir));
});

function makeDamArchive(string $path, array $entries): ZipArchive
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    $reopened = new ZipArchive;
    $reopened->open($path);

    return $reopened;
}

it('extracts ordinary entries and reports their relative paths', function () {
    $zip = makeDamArchive($this->workDir.'/a.zip', [
        'products.csv'            => 'sku,name',
        'assets/Root/hero.jpg'    => 'binary-a',
        'assets/Root/Sub/doc.pdf' => 'binary-b',
    ]);

    $extracted = (new SafeZipExtractor)->extract($zip, $this->workDir.'/out');

    expect($extracted)->toHaveCount(3)
        ->and($extracted)->toContain('products.csv', 'assets/Root/hero.jpg', 'assets/Root/Sub/doc.pdf')
        ->and(file_get_contents($this->workDir.'/out/assets/Root/Sub/doc.pdf'))->toBe('binary-b');
});

it('refuses entries that climb out of the extraction root', function () {
    $zip = makeDamArchive($this->workDir.'/slip.zip', [
        '../escaped.txt'      => 'nope',
        'nested/../../up.txt' => 'nope',
        'safe.txt'            => 'yes',
    ]);

    $extracted = (new SafeZipExtractor)->extract($zip, $this->workDir.'/out');

    expect($extracted)->toBe(['safe.txt'])
        ->and(file_exists($this->workDir.'/escaped.txt'))->toBeFalse()
        ->and(file_exists(dirname($this->workDir).'/up.txt'))->toBeFalse();
});

it('leaves nothing behind when an entry is rejected by the caller', function () {
    $zip = makeDamArchive($this->workDir.'/filtered.zip', [
        'keep.csv'   => 'a',
        'reject.exe' => 'b',
    ]);

    $extracted = (new SafeZipExtractor)->extract(
        $zip,
        $this->workDir.'/out',
        fn (string $path, string $extension): bool => $extension !== 'exe'
    );

    expect($extracted)->toBe(['keep.csv'])
        ->and(file_exists($this->workDir.'/out/reject.exe'))->toBeFalse()
        ->and(glob($this->workDir.'/out/*.part'))->toBe([]);
});

it('skips entries larger than the per-entry cap', function () {
    $zip = makeDamArchive($this->workDir.'/big.zip', [
        'small.txt' => 'tiny',
        'big.txt'   => str_repeat('x', 4096),
    ]);

    $extracted = (new SafeZipExtractor(maxEntrySize: 1024))->extract($zip, $this->workDir.'/out');

    expect($extracted)->toBe(['small.txt']);
});

it('stops extracting once the total size cap is reached', function () {
    $zip = makeDamArchive($this->workDir.'/total.zip', [
        'a.txt' => str_repeat('a', 600),
        'b.txt' => str_repeat('b', 600),
    ]);

    $extracted = (new SafeZipExtractor(maxTotalSize: 1000))->extract($zip, $this->workDir.'/out');

    expect($extracted)->toHaveCount(1);
});

it('rejects an archive holding more entries than allowed', function () {
    $zip = makeDamArchive($this->workDir.'/many.zip', [
        'a.txt' => 'a',
        'b.txt' => 'b',
        'c.txt' => 'c',
    ]);

    $rejection = (new SafeZipExtractor(maxEntries: 2))->rejectionReason($zip);

    expect($rejection['key'])->toBe('zip-too-many-entries')
        ->and($rejection['replace'])->toMatchArray(['count' => 3, 'limit' => 2]);
});

it('rejects an archive whose contents expand beyond the total cap', function () {
    $zip = makeDamArchive($this->workDir.'/bomb.zip', [
        'a.txt' => str_repeat('a', 5000),
    ]);

    $rejection = (new SafeZipExtractor(maxTotalSize: 1000, maxCompressionRatio: 0))->rejectionReason($zip);

    expect($rejection['key'])->toBe('zip-contents-too-large');
});

it('rejects an archive with a suspicious compression ratio', function () {
    $zip = makeDamArchive($this->workDir.'/ratio.zip', [
        'a.txt' => str_repeat('a', 200000),
    ]);

    $rejection = (new SafeZipExtractor(maxCompressionRatio: 5))->rejectionReason($zip);

    expect($rejection['key'])->toBe('zip-compression-suspicious');
});

it('accepts an archive that is within every limit', function () {
    $zip = makeDamArchive($this->workDir.'/ok.zip', ['a.txt' => 'hello world']);

    expect((new SafeZipExtractor)->rejectionReason($zip))->toBeNull();
});
