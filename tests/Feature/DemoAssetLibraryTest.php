<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

function damDemoRoot(): string
{
    return realpath(__DIR__.'/../../src/Database/Data/demo-assets')
        .DIRECTORY_SEPARATOR.'Root';
}

function damDemoFiles(string $subPath = ''): array
{
    $base = damDemoRoot().($subPath !== '' ? DIRECTORY_SEPARATOR.$subPath : '');

    if (! is_dir($base)) {
        return [];
    }

    $paths = [];

    foreach (Finder::create()->files()->in($base) as $file) {
        $paths[] = str_replace('\\', '/', $file->getRelativePathname());
    }

    sort($paths);

    return $paths;
}

it('ships 28 image assets', function () {
    expect(damDemoFiles('Brand/Logos'))->toHaveCount(4)
        ->and(damDemoFiles('Product Photography/Apparel'))->toHaveCount(8)
        ->and(damDemoFiles('Product Photography/Audio'))->toHaveCount(6)
        ->and(damDemoFiles('Product Photography/Furniture'))->toHaveCount(5)
        ->and(damDemoFiles('Product Photography/Outdoor'))->toHaveCount(5);
});

it('ships 8 video assets', function () {
    expect(damDemoFiles('Marketing/Campaign Videos'))->toHaveCount(4)
        ->and(damDemoFiles('Marketing/Social Clips'))->toHaveCount(4);
});

it('ships 9 audio assets', function () {
    expect(damDemoFiles('Audio/Podcasts'))->toHaveCount(5)
        ->and(damDemoFiles('Audio/Sound Logos'))->toHaveCount(4);
});

it('ships 15 document assets', function () {
    expect(damDemoFiles('Brand/Guidelines'))->toHaveCount(2)
        ->and(damDemoFiles('Documents/Datasheets'))->toHaveCount(6)
        ->and(damDemoFiles('Documents/Contracts'))->toHaveCount(4)
        ->and(damDemoFiles('Documents/Price Lists'))->toHaveCount(3);
});

it('ships exactly 60 assets in 18 directories', function () {
    $directories = [];

    foreach (Finder::create()->directories()->in(damDemoRoot()) as $directory) {
        $directories[] = $directory->getRelativePathname();
    }

    expect(damDemoFiles())->toHaveCount(60)
        ->and($directories)->toHaveCount(18);
});

it('covers 14 distinct extensions', function () {
    $extensions = array_map(
        fn (string $path): string => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
        damDemoFiles()
    );

    expect(array_unique($extensions))->toHaveCount(14);
});

it('has no forbidden extension and no empty file', function () {
    $forbidden = ['php', 'phtml', 'phar', 'js', 'py', 'sh', 'bat', 'pl', 'cgi',
        'asp', 'aspx', 'jsp', 'exe', 'rb', 'jar', 'html', 'htm', 'xhtml', 'shtml', 'hta'];

    foreach (damDemoFiles() as $path) {
        expect(strtolower(pathinfo($path, PATHINFO_EXTENSION)))->not->toBeIn($forbidden)
            ->and(filesize(damDemoRoot().'/'.$path))->toBeGreaterThan(0);
    }
});

it('stays under the 12 MB size budget', function () {
    $bytes = 0;

    foreach (damDemoFiles() as $path) {
        $bytes += filesize(damDemoRoot().'/'.$path);
    }

    expect($bytes)->toBeLessThan(12 * 1024 * 1024);
});
