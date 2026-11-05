<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Support\AssetBundleReader;
use Webkul\DAM\Support\PreparedBundle;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;

function damBundlePng(): string
{
    return base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
    );
}

beforeEach(function (): void {
    $this->damResetAssetTables();

    Storage::fake('private');
    Storage::fake('public');

    $this->bundle = function (array $entries, int $trackId = 41, ?array $filters = null): object {
        $archive = Storage::disk('private')->path('imports/bundle.zip');

        if (! is_dir(dirname($archive))) {
            mkdir(dirname($archive), 0755, true);
        }

        $zip = new ZipArchive;
        $zip->open($archive, ZipArchive::CREATE);

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return new class($trackId, $filters) implements JobTrackContract
        {
            public string $file_path = 'imports/bundle.zip';

            public ?object $jobInstance = null;

            public function __construct(public int $id, ?array $filters = null)
            {
                if ($filters !== null) {
                    $this->jobInstance = (object) ['filters' => $filters];
                }
            }
        };
    };
});

it('synthesizes a manifest whose directories nest under the default Root base', function () {
    Directory::create(['name' => 'Root', 'parent_id' => null]);

    $track = ($this->bundle)([
        'Marketing/hero.png' => damBundlePng(),
        'Docs/spec.png'      => damBundlePng(),
    ], 41);

    $prepared = app(AssetBundleReader::class)->prepare($track);

    expect($prepared)->toBeInstanceOf(PreparedBundle::class)
        ->and($prepared->dataFile)->toBe('imports/bundles/41/'.AssetBundleReader::GENERATED_DATA_FILE);

    $lines = array_values(array_filter(explode("\n", trim(Storage::disk('private')->get($prepared->dataFile))), 'strlen'));

    expect($lines[0])->toBe('path,directory,file_name');

    $byPath = collect(array_slice($lines, 1))->map(fn ($line) => str_getcsv($line))->keyBy(0);

    expect($byPath->has('Marketing/hero.png'))->toBeTrue()
        ->and($byPath['Marketing/hero.png'][1])->toBe('Root/Marketing')
        ->and($byPath['Marketing/hero.png'][2])->toBe('hero.png')
        ->and($byPath['Docs/spec.png'][1])->toBe('Root/Docs');

    expect(Asset::count())->toBe(0);
});

it('places a root-level media entry directly under the base directory instead of dropping it', function () {
    Directory::create(['name' => 'Root', 'parent_id' => null]);

    $track = ($this->bundle)([
        'cat.png'            => damBundlePng(),
        'holidays/beach.png' => damBundlePng(),
    ], 46);

    $prepared = app(AssetBundleReader::class)->prepare($track);

    $byPath = collect(array_filter(explode("\n", trim(Storage::disk('private')->get($prepared->dataFile))), 'strlen'))
        ->slice(1)
        ->map(fn ($line) => str_getcsv($line))
        ->keyBy(0);

    expect($byPath->has('cat.png'))->toBeTrue()
        ->and($byPath['cat.png'][1])->toBe('Root')
        ->and($byPath['holidays/beach.png'][1])->toBe('Root/holidays');
});

it('nests the synthesized directories under the chosen directory from filters', function () {
    $root = Directory::create(['name' => 'Root', 'parent_id' => null]);
    $marketing = Directory::create(['name' => 'Marketing', 'parent_id' => $root->id]);

    $track = ($this->bundle)([
        'logo.png'     => damBundlePng(),
        'summer/a.png' => damBundlePng(),
    ], 47, ['import_directory' => $marketing->id]);

    $prepared = app(AssetBundleReader::class)->prepare($track);

    $byPath = collect(array_filter(explode("\n", trim(Storage::disk('private')->get($prepared->dataFile))), 'strlen'))
        ->slice(1)
        ->map(fn ($line) => str_getcsv($line))
        ->keyBy(0);

    expect($byPath['logo.png'][1])->toBe('Root/Marketing')
        ->and($byPath['summer/a.png'][1])->toBe('Root/Marketing/summer');
});

it('returns the bundled manifest and stages the media when a data file is present', function () {
    $track = ($this->bundle)([
        'products.csv'                   => "sku,name\nsku-1,One",
        'assets/Root/Marketing/hero.png' => damBundlePng(),
    ], 42);

    $prepared = app(AssetBundleReader::class)->prepare($track);

    expect($prepared->dataFile)->toBe('imports/bundles/42/products.csv')
        ->and($prepared->mediaDirectory)->toBe(AssetBundleReader::MEDIA_DIRECTORY_PREFIX.'42')
        ->and(Storage::disk('public')->exists($prepared->mediaDirectory.'/assets/Root/Marketing/hero.png'))->toBeTrue();
});

it('reads the stored digest with the algorithm the local comparison is made in', function () {
    Storage::disk('private')->put('assets/Root/Marketing/hero.png', 'hero-bytes');

    $stored = Storage::disk('private')->checksum('assets/Root/Marketing/hero.png', ['checksum_algo' => 'md5']);

    expect($stored)->toBe(md5('hero-bytes'));
});

it('throws when the archive carries neither a manifest nor servable media', function () {
    $track = ($this->bundle)([
        'assets/Root/Docs/broken.png' => 'this is not an image',
    ]);

    expect(fn () => app(AssetBundleReader::class)->prepare($track))
        ->toThrow(RuntimeException::class);
});

it('refuses an executable disguised inside the asset tree', function () {
    $track = ($this->bundle)([
        'products.csv'            => "sku\nsku-1",
        'assets/Root/payload.php' => '<?php echo "owned";',
    ], 44);

    $prepared = app(AssetBundleReader::class)->prepare($track);

    expect($prepared->dataFile)->toBe('imports/bundles/44/products.csv')
        ->and($prepared->mediaDirectory)->toBeNull();
});
