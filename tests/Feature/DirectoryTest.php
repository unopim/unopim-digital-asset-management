<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\CopyDirectoryStructure;
use Webkul\DAM\Jobs\DeleteDirectory;
use Webkul\DAM\Jobs\MoveDirectoryStructure;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return all directories with correct structure', function () {
    $response = $this->get(route('admin.dam.directory.index'));
    $response->assertOk();
    $response->assertJsonStructure([
        'data',
    ]);
});

it('should return the children directory data when directory exists', function () {
    $parent = Directory::factory()->create();

    $children = Directory::factory()->count(3)->make([
        'parent_id' => $parent->id,
    ]);

    $parent->setRelation('children', $children);

    $this->mock(DirectoryRepository::class, function ($mock) use ($parent) {
        $mock->shouldReceive('getDirectoryTree')
            ->with($parent->id)
            ->andReturn(collect([$parent]));
    });

    $response = $this->get(route('admin.dam.directory.children', $parent->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'parent_id',
            'children',
        ],
    ]);

    $responseData = $response->json('data');
    expect($responseData['id'])->toBe($parent->id);
    expect($responseData['children'])->toHaveCount(3);
});

it('returns assets of the directory when directory exists (many-to-many)', function () {
    $directory = Directory::factory()->create();
    $assets = Asset::factory()->count(3)->create();

    $directory->assets()->attach($assets->pluck('id')->toArray());

    $this->mock(DirectoryRepository::class, function ($mock) use ($directory) {
        $mock->shouldReceive('getDirectoryTree')
            ->with($directory->id)
            ->andReturn(collect([$directory]));
    });

    $response = $this->getJson(route('admin.dam.directory.assets', ['id' => $directory->id]));

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
    $response->assertJsonFragment(['id' => $assets[0]->id]);
});

it('should create new directory', function () {

    $disk = Directory::getAssetDisk();
    Storage::fake($disk);
    Storage::disk($disk)->makeDirectory('assets/New');

    $directory = Directory::factory()->create([
        'name'      => 'New',
        'parent_id' => null,
    ]);

    $data = [
        'name'      => 'Root Child',
        'parent_id' => $directory->id,
    ];

    $response = $this->post(route('admin.dam.directory.store'), $data);

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'name'      => 'Root Child',
            'parent_id' => $directory->id,
        ],
    ]);

    $this->assertDatabaseHas('dam_directories', [
        'name'      => 'Root Child',
        'parent_id' => $directory->id,
    ]);
});

it('updates a directory name and dispatches RenameDirectoryJob', function () {

    $disk = Directory::getAssetDisk();
    Storage::fake($disk);
    Storage::disk($disk)->makeDirectory('assets');

    $directory = Directory::factory()->create([
        'name' => 'Old Name',
    ]);

    $updateData = [
        'id'   => $directory->id,
        'name' => 'New Name',
    ];

    $response = $this->post(route('admin.dam.directory.update'), $updateData);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.index.directory.updated-success'),
        'data'    => [
            'id'   => $directory->id,
            'name' => 'New Name',
        ],
    ]);

    $this->assertDatabaseHas('dam_directories', [
        'id'   => $directory->id,
        'name' => 'New Name',
    ]);
});

it('should delete an existing directory', function () {
    Bus::fake();

    $directory = Directory::factory()->create([
        'name'      => 'New',
        'parent_id' => null,
    ]);

    $response = $this->delete(route('admin.dam.directory.destroy', $directory->id));

    $response->assertOk();
    Bus::assertDispatched(DeleteDirectory::class);
});

it('downloads a zip archive of the directory files and folders', function () {
    $directory = Directory::factory()->create([
        'name' => 'TestDirectory',
    ]);

    $this->mock(DirectoryRepository::class, function ($mock) use ($directory) {
        $mock->shouldReceive('findOrFail')
            ->with($directory->id)
            ->andReturn($directory);
    });

    $folderPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

    $disk = Directory::getAssetDisk();

    Storage::fake($disk);

    Storage::disk($disk)->put($folderPath.'/file1.txt', 'File 1 contents');
    Storage::disk($disk)->put($folderPath.'/subdir/file2.txt', 'File 2 contents');
    Storage::disk($disk)->makeDirectory($folderPath.'/subdir');

    $response = $this->get(route('admin.dam.directory.zip_download', ['id' => $directory->id]));

    $response->assertSuccessful();
    $response->assertHeader('content-disposition');

    $zipFileName = sprintf('%s.zip', $directory->name);
    $zipFilePath = public_path($zipFileName);

    $this->assertFileExists($zipFilePath);

    unlink($zipFilePath);
});

it('dispatches copy job when directory is copyable', function () {
    Bus::fake();

    $directory = Directory::factory()->create();

    $response = $this->post(route('admin.dam.directory.copy_structure'), [
        'id' => $directory->id,
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.index.directory.coping-in-progress'),
    ]);

    Bus::assertDispatched(CopyDirectoryStructure::class);
});

it('should dispatch move directory job', function () {
    Bus::fake();

    $parentDir = Directory::factory()->create(['name' => 'Parent']);
    $childDir = Directory::factory()->create(['name' => 'Child', 'parent_id' => $parentDir->id]);
    $targetDir = Directory::factory()->create(['name' => 'Target']);

    $response = $this->post(route('admin.dam.directory.moved'), [
        'move_item_id'  => $childDir->id,
        'new_parent_id' => $targetDir->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.moved-success'),
        ]);

    Bus::assertDispatched(MoveDirectoryStructure::class);
});

it('should not delete a non-deletable directory', function () {
    $rootDirectory = Directory::find(1);

    if (! $rootDirectory) {
        $rootDirectory = Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    }

    $response = $this->delete(route('admin.dam.directory.destroy', $rootDirectory->id));

    $response->assertStatus(403)
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.can-not-deleted'),
        ]);
});

it('should return 404 when directory not found for children', function () {
    $this->mock(DirectoryRepository::class, function ($mock) {
        $mock->shouldReceive('getDirectoryTree')
            ->with(99999)
            ->andReturn(collect([]));
    });

    $response = $this->get(route('admin.dam.directory.children', 99999));

    $response->assertStatus(404)
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.not-found'),
        ]);
});

it('should return 404 when directory not found for assets', function () {
    $this->mock(DirectoryRepository::class, function ($mock) {
        $mock->shouldReceive('getDirectoryTree')
            ->with(99999)
            ->andReturn(collect([]));
    });

    $response = $this->getJson(route('admin.dam.directory.assets', 99999));

    $response->assertStatus(404)
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.not-found'),
        ]);
});

it('should not copy a non-copyable directory', function () {
    $rootDirectory = Directory::find(1);

    if (! $rootDirectory) {
        $rootDirectory = Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    }

    $response = $this->post(route('admin.dam.directory.copy_structure'), [
        'id' => $rootDirectory->id,
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.can-not-copy'),
        ]);
});

it('should validate directory name is required on store', function () {
    $response = $this->postJson(route('admin.dam.directory.store'), [
        'parent_id' => 1,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('should validate move_item_id and new_parent_id are required', function () {
    $response = $this->postJson(route('admin.dam.directory.moved'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['move_item_id', 'new_parent_id']);
});
