<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Support\AssetBundleReader;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;

/*
 * Reading a bundle has to reproduce the source DAM tree and stay idempotent: an import
 * re-run must not duplicate assets, nor overwrite a binary edited since the first run.
 */

beforeEach(function (): void {
    $this->damResetAssetTables();

    Storage::fake('private');
    Bus::fake();

    $this->bundle = function (array $entries, int $trackId = 41): object {
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

        return new class($trackId) implements JobTrackContract
        {
            public string $file_path = 'imports/bundle.zip';

            public function __construct(public int $id) {}
        };
    };
});

it('returns the archive data file and recreates the asset tree', function () {
    $track = ($this->bundle)([
        'products.csv'                   => "sku,name\nsku-1,One",
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
        'assets/Root/Docs/spec.pdf'      => 'spec-bytes',
    ]);

    $dataFile = (new AssetBundleReader)->prepare($track);

    expect($dataFile)->toBe('imports/bundles/41/products.csv');

    $root = Directory::where('name', 'Root')->whereNull('parent_id')->first();

    expect($root)->not->toBeNull()
        ->and(Directory::where('name', 'Marketing')->where('parent_id', $root->id)->exists())->toBeTrue()
        ->and(Directory::where('name', 'Docs')->where('parent_id', $root->id)->exists())->toBeTrue();

    $hero = Asset::where('path', 'assets/Root/Marketing/hero.jpg')->first();

    expect($hero)->not->toBeNull()
        ->and($hero->file_name)->toBe('hero.jpg')
        ->and($hero->extension)->toBe('jpg')
        ->and(Storage::disk('private')->get('assets/Root/Marketing/hero.jpg'))->toBe('hero-bytes');
});

it('queues metadata extraction for each ingested asset', function () {
    ($this->bundle)([
        'products.csv'                   => "sku\nsku-1",
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
    ]);

    (new AssetBundleReader)->prepare(($this->bundle)([
        'products.csv'                   => "sku\nsku-1",
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
    ]));

    Bus::assertDispatched(ProcessAssetUpload::class);
});

it('reuses an asset already present at the same path', function () {
    $entries = [
        'products.csv'                   => "sku\nsku-1",
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
    ];

    (new AssetBundleReader)->prepare(($this->bundle)($entries, 41));

    $firstId = Asset::where('path', 'assets/Root/Marketing/hero.jpg')->value('id');

    Storage::disk('private')->put('assets/Root/Marketing/hero.jpg', 'edited-in-the-explorer');

    (new AssetBundleReader)->prepare(($this->bundle)($entries, 42));

    expect(Asset::where('path', 'assets/Root/Marketing/hero.jpg')->count())->toBe(1)
        ->and(Asset::where('path', 'assets/Root/Marketing/hero.jpg')->value('id'))->toBe($firstId)
        ->and(Storage::disk('private')->get('assets/Root/Marketing/hero.jpg'))->toBe('edited-in-the-explorer');
});

it('does not duplicate directories across repeated imports', function () {
    $entries = [
        'products.csv'                   => "sku\nsku-1",
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
    ];

    (new AssetBundleReader)->prepare(($this->bundle)($entries, 41));
    (new AssetBundleReader)->prepare(($this->bundle)($entries, 42));

    expect(Directory::where('name', 'Root')->count())->toBe(1)
        ->and(Directory::where('name', 'Marketing')->count())->toBe(1);
});

it('fails when the archive carries no data file', function () {
    $track = ($this->bundle)([
        'assets/Root/Marketing/hero.jpg' => 'hero-bytes',
    ]);

    expect(fn () => (new AssetBundleReader)->prepare($track))
        ->toThrow(RuntimeException::class);
});

it('refuses an executable disguised inside the asset tree', function () {
    $track = ($this->bundle)([
        'products.csv'               => "sku\nsku-1",
        'assets/Root/payload.php'    => '<?php echo "owned";',
    ]);

    (new AssetBundleReader)->prepare($track);

    expect(Asset::where('path', 'assets/Root/payload.php')->exists())->toBeFalse();
});
