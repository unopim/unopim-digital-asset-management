<?php

use Illuminate\Http\UploadedFile;
use Webkul\DAM\Services\MetadataExtractionService;

// Expose protected helpers via anonymous subclass
function metaService(): MetadataExtractionService
{
    return new class extends MetadataExtractionService
    {
        public function callHandleArrayMetadata(array $m): array
        {
            return $this->handleArrayMetadata($m);
        }

        public function callIsErrorResponse($d): bool
        {
            return $this->isErrorResponse($d);
        }

        public function callErrorResponse(string $msg): array
        {
            return $this->errorResponse($msg);
        }
    };
}

// ── isErrorResponse ───────────────────────────────────────────────────────

it('detects error response correctly', function () {
    $svc = metaService();

    expect($svc->callIsErrorResponse(['success' => false, 'message' => 'fail']))->toBeTrue();
});

it('treats normal metadata array as non-error', function () {
    $svc = metaService();

    expect($svc->callIsErrorResponse(['FileType' => 'JPEG']))->toBeFalse();
});

it('treats empty array as non-error', function () {
    $svc = metaService();

    expect($svc->callIsErrorResponse([]))->toBeFalse();
});

it('treats array with success=true as non-error', function () {
    $svc = metaService();

    expect($svc->callIsErrorResponse(['success' => true]))->toBeFalse();
});

it('treats non-array as non-error', function () {
    $svc = metaService();

    expect($svc->callIsErrorResponse('string'))->toBeFalse();
    expect($svc->callIsErrorResponse(null))->toBeFalse();
});

// ── errorResponse ─────────────────────────────────────────────────────────

it('returns correct error response shape', function () {
    $svc = metaService();
    $resp = $svc->callErrorResponse('Something went wrong');

    expect($resp)->toBe(['success' => false, 'message' => 'Something went wrong']);
});

// ── handleArrayMetadata ───────────────────────────────────────────────────

it('passes through scalar values unchanged', function () {
    $svc = metaService();
    $result = $svc->callHandleArrayMetadata([
        'FileType'   => 'JPEG',
        'ImageWidth' => 800,
        'GPSAltitude'=> 120.5,
    ]);

    expect($result)->toBe([
        'FileType'    => 'JPEG',
        'ImageWidth'  => 800,
        'GPSAltitude' => 120.5,
    ]);
});

it('joins list arrays with comma', function () {
    $svc = metaService();
    $result = $svc->callHandleArrayMetadata([
        'Keywords' => ['nature', 'outdoor', 'travel'],
    ]);

    expect($result['Keywords'])->toBe('nature,outdoor,travel');
});

it('flattens associative sub-arrays with colon separator', function () {
    $svc = metaService();
    $result = $svc->callHandleArrayMetadata([
        'GPS' => ['Latitude' => '48.8566', 'Longitude' => '2.3522'],
    ]);

    expect($result)->toHaveKey('GPS:Latitude');
    expect($result)->toHaveKey('GPS:Longitude');
    expect($result)->not->toHaveKey('GPS');
    expect($result['GPS:Latitude'])->toBe('48.8566');
});

it('handles mixed scalar and nested values together', function () {
    $svc = metaService();
    $result = $svc->callHandleArrayMetadata([
        'FileType' => 'PNG',
        'Tags'     => ['a', 'b'],
        'EXIF'     => ['Make' => 'Canon'],
    ]);

    expect($result)->toHaveKey('FileType');
    expect($result['Tags'])->toBe('a,b');
    expect($result)->toHaveKey('EXIF:Make');
});

it('returns empty array unchanged', function () {
    $svc = metaService();

    expect($svc->callHandleArrayMetadata([]))->toBe([]);
});

// ── extractMetadata – safe no-op paths ────────────────────────────────────

it('returns empty array for empty path with no localPath', function () {
    $svc = new MetadataExtractionService;

    expect($svc->extractMetadata('', 'local'))->toBe([]);
});

it('returns empty array when temp file cannot be resolved', function () {
    $svc = new MetadataExtractionService;

    // Non-existent path on non-s3 disk → getFileTempPath returns null → []
    expect($svc->extractMetadata('/nonexistent/path/file.jpg', 'local'))->toBe([]);
});

// ── getFileTempPath (non-s3) ──────────────────────────────────────────────

it('returns file path when file exists on non-s3 disk', function () {
    $svc = new MetadataExtractionService;
    $file = UploadedFile::fake()->image('test.jpg');
    $path = $file->getRealPath();

    expect($svc->getFileTempPath($path, 'local'))->toBe($path);
});

it('returns null when file does not exist on non-s3 disk', function () {
    $svc = new MetadataExtractionService;

    expect($svc->getFileTempPath('/tmp/this_file_does_not_exist_xyz.jpg', 'local'))->toBeNull();
});

// ── extractMetadata – forbidden file ─────────────────────────────────────

it('returns empty array for forbidden file type', function () {
    // Create a temp PHP file (forbidden by extension)
    $tmp = tempnam(sys_get_temp_dir(), 'dam_test_').'.php';
    file_put_contents($tmp, '<?php echo 1;');

    $svc = new MetadataExtractionService;

    try {
        // Pass $tmp as path so ext='php' is detected by isForbiddenFile
        $result = $svc->extractMetadata($tmp, 'local', $tmp);
        expect($result)->toBe([]);
    } finally {
        @unlink($tmp);
    }
});
