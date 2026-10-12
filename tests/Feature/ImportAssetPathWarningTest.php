<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\CategoryField;
use Webkul\DAM\Helpers\Importers\Category\Importer as CategoryImporter;
use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as ProductImporter;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

/*
 * A data file row whose asset path resolves to nothing must still import, and the job
 * log must name the row, the record and the column so the path can be corrected.
 */

uses(DatabaseTransactions::class);

function damWarningAsset(string $path): Asset
{
    return Asset::create([
        'file_name' => basename($path),
        'file_type' => 'document',
        'file_size' => 10,
        'mime_type' => 'application/pdf',
        'extension' => pathinfo($path, PATHINFO_EXTENSION),
        'path'      => $path,
    ]);
}

/**
 * @return list<string>
 */
function damWarnings(TestHandler $handler): array
{
    return array_values(array_map(
        fn ($record): string => $record->message,
        array_filter($handler->getRecords(), fn ($record): bool => $record->level === Level::Warning)
    ));
}

beforeEach(function (): void {
    $this->logHandler = new TestHandler;
    $this->logger = new Logger('dam-test', [$this->logHandler]);
});

describe('product import', function () {
    beforeEach(function (): void {
        $this->assetCode = 'asset_'.Str::random(8);

        Attribute::factory()->create(['code' => $this->assetCode, 'type' => Asset::ASSET_ATTRIBUTE_TYPE]);

        $this->family = AttributeFamily::factory()->create();
        AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
        AttributeFamily::factory()->linkAttributesToFamily(
            $this->family,
            Attribute::whereIn('code', ['sku', 'status', $this->assetCode])->get()
        );

        $this->asset = damWarningAsset('assets/Root/Documents/Example/'.Str::random(6).'.pdf');

        $this->importer = app(ProductImporter::class)
            ->setImport(JobTrack::factory()->create())
            ->setLogger($this->logger)
            ->setErrorHelper(new Error);

        $this->row = fn (string $value, string $sku = 'DEMO-001'): array => [
            'sku'              => $sku,
            'type'             => 'simple',
            'attribute_family' => $this->family->code,
            'channel'          => core()->getDefaultChannelCode(),
            'locale'           => core()->getRequestedLocaleCode(),
            $this->assetCode   => $value,
        ];
    });

    it('warns about a path missing its directory separator and keeps the row valid', function () {
        $path = 'assets/Root/Documents/Examplemanual.pdf';

        expect($this->importer->validateRow(($this->row)($path), 2))->toBeTrue()
            ->and(damWarnings($this->logHandler))->toBe([
                trans('dam::app.data-transfer.bundle.asset-not-found', [
                    'row'        => 2,
                    'identifier' => 'DEMO-001',
                    'column'     => $this->assetCode,
                    'path'       => $path,
                ]),
            ]);
    });

    it('warns only about the unresolved paths in a mixed value', function () {
        $value = $this->asset->path.', assets/Root/missing.pdf';

        expect($this->importer->validateRow(($this->row)($value), 3))->toBeTrue();

        $warnings = damWarnings($this->logHandler);

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0])->toContain('assets/Root/missing.pdf')
            ->and($warnings[0])->not->toContain($this->asset->path);
    });

    it('warns once per row that references the same unresolved path', function () {
        $this->importer->validateRow(($this->row)('assets/Root/missing.pdf', 'DEMO-001'), 2);
        $this->importer->validateRow(($this->row)('assets/Root/missing.pdf', 'DEMO-002'), 3);

        expect(damWarnings($this->logHandler))->toHaveCount(2);
    });

    it('does not warn again when a row is validated twice', function () {
        $this->importer->validateRow(($this->row)('assets/Root/missing.pdf'), 2);
        $this->importer->validateRow(($this->row)('assets/Root/missing.pdf'), 2);

        expect(damWarnings($this->logHandler))->toHaveCount(1);
    });

    it('logs nothing when every path resolves', function () {
        expect($this->importer->validateRow(($this->row)($this->asset->path), 2))->toBeTrue()
            ->and(damWarnings($this->logHandler))->toBe([]);
    });
});

describe('category import', function () {
    beforeEach(function (): void {
        $this->fieldCode = 'cat_asset_'.Str::random(6);

        CategoryField::factory()->create([
            'code'             => $this->fieldCode,
            'type'             => 'asset',
            'status'           => 1,
            'value_per_locale' => 0,
        ]);

        $this->importer = app(CategoryImporter::class)
            ->setImport(JobTrack::factory()->create())
            ->setLogger($this->logger)
            ->setErrorHelper(new Error);

        $this->importer->getCategoryFields();
    });

    it('warns about an unresolved path and keeps the row valid', function () {
        $row = [
            'code'           => 'demo_category',
            'parent'         => '',
            'name'           => 'Demo category',
            'locale'         => core()->getRequestedLocaleCode(),
            $this->fieldCode => 'assets/Root/missing.jpg',
        ];

        expect($this->importer->validateRow($row, 4))->toBeTrue()
            ->and(damWarnings($this->logHandler))->toBe([
                trans('dam::app.data-transfer.bundle.asset-not-found', [
                    'row'        => 4,
                    'identifier' => 'demo_category',
                    'column'     => $this->fieldCode,
                    'path'       => 'assets/Root/missing.jpg',
                ]),
            ]);
    });
});

describe('import job', function () {
    beforeEach(function (): void {
        $this->assetCode = 'asset_'.Str::random(8);
        $this->textCode = 'text_'.Str::random(8);

        Attribute::factory()->create(['code' => $this->assetCode, 'type' => Asset::ASSET_ATTRIBUTE_TYPE]);
        Attribute::factory()->create(['code' => $this->textCode, 'type' => 'text']);

        $this->family = AttributeFamily::factory()->create();
        AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
        AttributeFamily::factory()->linkAttributesToFamily(
            $this->family,
            Attribute::whereIn('code', ['sku', 'status', $this->assetCode, $this->textCode])->get()
        );

        $this->asset = damWarningAsset('assets/Root/Documents/Example/'.Str::random(6).'.pdf');

        $this->csvPath = 'imports/dam-warning-'.Str::random(8).'.csv';
    });

    afterEach(function (): void {
        Storage::disk('private')->delete($this->csvPath);

        if (isset($this->track)) {
            File::deleteDirectory(storage_path('logs/job-tracker/'.$this->track->id));
        }
    });

    it('imports every row, skipping only the unresolved path, and logs a warning for it', function () {
        $sku = 'DEMO-'.Str::random(6);
        $nextSku = 'DEMO-'.Str::random(6);
        $missingPath = 'assets/Root/Documents/Examplemanual.pdf';

        $scope = [core()->getRequestedLocaleCode(), core()->getDefaultChannelCode(), '', ''];

        $lines = [
            ['locale', 'channel', 'parent', 'variant_structure', 'sku', 'type', 'attribute_family', 'status', $this->textCode, $this->assetCode],
            [...$scope, $sku, 'simple', $this->family->code, 'true', 'First', $this->asset->path.','.$missingPath],
            [...$scope, $nextSku, 'simple', $this->family->code, 'true', 'Second', $this->asset->path],
        ];

        Storage::disk('private')->put(
            $this->csvPath,
            implode("\n", array_map(fn (array $line): string => implode(',', array_map(
                fn (string $cell): string => '"'.$cell.'"',
                $line
            )), $lines))."\n"
        );

        $jobInstance = JobInstances::create([
            'code'                => 'dam-warning-'.Str::random(8),
            'entity_type'         => 'products',
            'type'                => 'import',
            'action'              => Import::ACTION_APPEND,
            'validation_strategy' => Import::VALIDATION_STRATEGY_STOP_ON_ERROR,
            'allowed_errors'      => 0,
            'field_separator'     => ',',
            'file_path'           => $this->csvPath,
        ]);

        $this->track = JobTrack::create([
            'type'                => 'import',
            'state'               => Import::STATE_PENDING,
            'action'              => $jobInstance->action,
            'validation_strategy' => $jobInstance->validation_strategy,
            'allowed_errors'      => $jobInstance->allowed_errors,
            'field_separator'     => ',',
            'file_path'           => $jobInstance->file_path,
            'meta'                => $jobInstance->toArray(),
            'job_instances_id'    => $jobInstance->id,
            'user_id'             => Admin::value('id'),
        ]);

        (new ImportTrackBatch($this->track))->handle();

        $this->track->refresh();

        $first = Product::where('sku', $sku)->first();
        $second = Product::where('sku', $nextSku)->first();

        expect($this->track->state)->toBe(Import::STATE_COMPLETED)
            ->and($this->track->errors_count)->toBe(0)
            ->and($first)->not->toBeNull()
            ->and($second)->not->toBeNull()
            ->and(data_get($first->values, ['common', $this->textCode]))->toBe('First')
            ->and(data_get($first->values, ['common', $this->assetCode]))->toBe((string) $this->asset->id)
            ->and(data_get($second->values, ['common', $this->assetCode]))->toBe((string) $this->asset->id);

        $log = File::get(storage_path(JobLogger::getJobLogPath($this->track->id)));

        expect($log)->toContain('WARNING')
            ->and($log)->toContain(trans('dam::app.data-transfer.bundle.asset-not-found', [
                'row'        => 1,
                'identifier' => $sku,
                'column'     => $this->assetCode,
                'path'       => $missingPath,
            ]))
            ->and(substr_count($log, 'WARNING'))->toBe(1);
    });
});
