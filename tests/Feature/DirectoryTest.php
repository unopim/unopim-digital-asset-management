<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

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

    $children = Directory::factory()->count(3)->create([
        'parent_id' => $parent->id,
    ]);

    $response = $this->get(route('admin.dam.directory.children', $parent->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'parent_id',
        ],
    ]);

    $responseData = $response->json('data');
    expect($responseData['id'])->toBe($parent->id);
});

it('returns assets of the directory when directory exists (many-to-many)', function () {
    $directory = Directory::factory()->create();
    $assets = Asset::factory()->count(3)->create();

    $directory->assets()->attach($assets->pluck('id')->toArray());

    $this->mock(\Webkul\DAM\Repositories\DirectoryRepository::class, function ($mock) use ($directory) {
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

    Storage::fake('private');
    Storage::disk('private')->makeDirectory('assets/New');

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
    $directory = Directory::factory()->create([
        'name'      => 'New',
        'parent_id' => null,
    ]);
    $response = $this->delete(route('admin.dam.directory.destroy', $directory->id));
    $response->assertOk();
});

it('downloads a zip archive of the directory files and folders', function () {
    $directory = Directory::factory()->create([
        'name' => 'TestDirectory',
    ]);

    $this->mock(\Webkul\DAM\Repositories\DirectoryRepository::class, function ($mock) use ($directory) {
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

    $directory = Directory::factory()->create();

    $response = $this->post(route('admin.dam.directory.copy_structure'), [
        'id' => $directory->id,
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => trans('dam::app.admin.dam.index.directory.coping-in-progress'),
    ]);
});
