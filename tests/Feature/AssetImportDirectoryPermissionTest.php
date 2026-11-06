<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\Importers\Asset\Importer;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function damPngBytes(): string
{
    return base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
    );
}

function damImporterForUser(int $userId): array
{
    $jobInstance = JobInstances::factory()->importJob()->create([
        'entity_type' => 'assets',
        'filters'     => ['conflict_strategy' => 'skip'],
    ]);

    $jobTrack = JobTrack::factory()->create([
        'action'                => Import::ACTION_APPEND,
        'job_instances_id'      => $jobInstance->id,
        'images_directory_path' => 'imp',
        'user_id'               => $userId,
    ]);

    $importer = app(Importer::class);
    $importer->setImport($jobTrack);
    $importer->setErrorHelper(app(Error::class));

    return [$importer, $jobTrack];
}

beforeEach(function () {
    $this->damResetAssetTables();
    Storage::fake('local');
    Storage::fake(Directory::getAssetDisk());
    Bus::fake();

    // Seed the tree: Root → Brand (granted) + Private (denied).
    $disk = Directory::getAssetDisk();
    $this->root = Directory::create(['name' => 'Root', 'parent_id' => null]);
    Storage::disk($disk)->makeDirectory('assets/Root');

    $repository = app(DirectoryRepository::class);
    $this->brand = $repository->create(['name' => 'Brand', 'parent_id' => $this->root->id]);
    $this->private = $repository->create(['name' => 'Private', 'parent_id' => $this->root->id]);

    // A custom-role admin granted write access to Brand only.
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dam', 'data_transfer']]);
    $this->admin = Admin::factory()->create(['role_id' => $role->id]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $this->brand->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    app(DirectoryPermissionService::class)->flush();
});

it('imports into a directory the user is granted', function () {
    Storage::disk('local')->put('public/imp/assets/Root/Brand/a.png', damPngBytes());

    [$importer, $jobTrack] = damImporterForUser($this->admin->id);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [
            ['path' => 'assets/Root/Brand/a.png', 'file_name' => 'a.png', 'directory' => 'Root/Brand', 'tags' => '', 'properties' => ''],
        ],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(1)
        ->and(Asset::where('path', 'assets/Root/Brand/a.png')->exists())->toBeTrue();
});

it('blocks writing into a directory the user is not granted', function () {
    Storage::disk('local')->put('public/imp/assets/Root/Private/b.png', damPngBytes());

    [$importer, $jobTrack] = damImporterForUser($this->admin->id);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [
            ['path' => 'assets/Root/Private/b.png', 'file_name' => 'b.png', 'directory' => 'Root/Private', 'tags' => '', 'properties' => ''],
        ],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(0)
        ->and(Asset::where('path', 'assets/Root/Private/b.png')->exists())->toBeFalse();
});

it('blocks creating a new directory under a root the user is not granted', function () {
    Storage::disk('local')->put('public/imp/assets/Root/New/c.png', damPngBytes());

    [$importer, $jobTrack] = damImporterForUser($this->admin->id);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [
            ['path' => 'assets/Root/New/c.png', 'file_name' => 'c.png', 'directory' => 'Root/New', 'tags' => '', 'properties' => ''],
        ],
    ]);

    $importer->importBatch($batch);

    expect($importer->getCreatedItemsCount())->toBe(0)
        ->and(Directory::where('name', 'New')->exists())->toBeFalse()
        ->and(Asset::where('path', 'assets/Root/New/c.png')->exists())->toBeFalse();
});
