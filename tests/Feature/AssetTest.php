<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

// Index Page for DAM Asset
it('should return the asset index page', function () {
    $this->get(route('admin.dam.assets.index'))
        ->assertOk()
        ->assertSeeText(trans('dam::app.admin.dam.index.title'));
});

// Return the Edit Page
it('should return the asset edit page', function () {
    $assetId = Asset::factory()->create()->id;

    $this->get(route('admin.dam.assets.edit', $assetId))
        ->assertOk()
        ->assertSeeText(trans('dam::app.admin.dam.asset.edit.title'))
        ->assertSeeText(trans('dam::app.admin.dam.asset.edit.save-btn'))
        ->assertSeeText(trans('dam::app.admin.dam.asset.edit.save-btn'));
});

// Show the Asset
it('should return the asset detail page', function () {
    $asset = Asset::factory()->create();

    $this->get(route('admin.dam.assets.show', $asset->id))
        ->assertOk()
        ->assertSeeText($asset->name ?? $asset->file_name);
});

// Update the Asset
it('should update the asset details successfully', function () {
    $asset = Asset::factory()->create();

    $updateData = [
        'id'         => $asset->id,
        'file_name'  => 'updated-name.png',
        'tags'       => ['tag1', 'tag2'],
    ];

    $this->put(route('admin.dam.assets.update', $asset->id), $updateData)
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.update-success'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), [
        'id'        => $asset->id,
        'file_name' => 'updated-name.png',
    ]);
});

// Upload Asset File
it('should upload the asset file to the specified directory', function () {
    Storage::fake('private');
    Storage::disk('private')->makeDirectory('assets/New');

    $directory = Directory::factory()->create([
        'name'      => 'New',
        'parent_id' => null,
    ]);

    $file = UploadedFile::fake()->image('simple.png', 600, 600)->size(23);
    $uploadData = [
        'files'        => [$file],
        'directory_id' => $directory->id,
    ];
    $response = $this->postJson(route('admin.dam.assets.upload'), $uploadData);
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.file-upload-success'),
        ])
        ->assertJsonStructure(['files' => [['id', 'file_name', 'path']]]);

    $uploadedFileName = $response->json('files.0.file_name');
    $uploadedPath = $response->json('files.0.path');

    Storage::disk('private')->assertExists($uploadedPath);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), [
        'file_name' => $uploadedFileName,
        'path'      => $uploadedPath,
    ]);
});

// Re-Upload Asset File
it('should re-upload the asset file to the specified directory and update the asset record', function () {
    Storage::fake('private');

    $directory = Directory::factory()->create(['name' => 'Root']);

    $initialFilePath = 'assets/Root/original.png';
    $asset = Asset::factory()->create([
        'file_name' => 'original.png',
        'path'      => $initialFilePath,
    ]);

    $asset->directories()->attach($directory->id);

    Storage::disk('private')->put($initialFilePath, 'dummy content');

    $newFile = UploadedFile::fake()->image('simple.png', 600, 600)->size(23);

    $response = $this->postJson(route('admin.dam.assets.re_upload'), [
        'file'     => $newFile,
        'asset_id' => $asset->id,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
        ])
        ->assertJsonPath('file.id', $asset->id);

    Storage::disk('private')->assertMissing($initialFilePath);

    $newFileName = $response->json('file.file_name');
    $expectedNewPath = 'assets/Root/'.$newFileName;

    Storage::disk('private')->assertExists($expectedNewPath);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), [
        'id'        => $asset->id,
        'file_name' => $newFileName,
        'path'      => $expectedNewPath,
    ]);
});

// Delete the asset
it('should delete a asset successfully', function () {
    $assetId = Asset::factory()->create()->id;

    $this->delete(route('admin.dam.assets.destroy', $assetId))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('dam::app.admin.dam.asset.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(Asset::class), ['id' => $assetId]);
});

// Mass Delete the Asset
it('should mass delete the asset successfully', function () {
    $assetIds = Asset::factory()->createMany(3)->pluck('id')->toArray();

    $this->post(route('admin.dam.assets.mass_delete'), ['indices' => $assetIds])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('dam::app.admin.dam.asset.datagrid.mass-delete-success')]);

    foreach ($assetIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Asset::class), ['id' => $id]);
    }
});

// Download the Asset
it('should allow downloading the asset file', function () {
    Storage::fake('private');
    Storage::disk('private')->put('assets/Root/sample.pdf', 'dummy content');

    $asset = Asset::factory()->create([
        'file_name' => 'sample.pdf',
        'path'      => 'assets/Root/sample.pdf',
    ]);

    $response = $this->get(route('admin.dam.assets.download', $asset->id));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
});

// Custom Download Asset
it('should allow custom downloading of the asset', function () {
    Storage::disk('private')->put(
        'assets/Root/sample.jpg',
        file_get_contents(base_path('public/storage/Fixtures/sample.jpg'))
    );

    $asset = Asset::factory()->create([
        'path'      => 'assets/Root/sample.jpg',
        'file_name' => 'sample.jpg',
    ]);

    $response = $this->get(route('admin.dam.assets.custom_download', [
        'id'     => $asset->id,
        'format' => 'png',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

// Rename File
it('should rename the file name', function () {
    Storage::fake('private');

    $originalName = 'original-name.pdf';
    $newName = 'renamed-file.pdf';

    $directory = 'uploads/assets/';
    $originalPath = $directory.$originalName;
    $newPath = $directory.$newName;

    Storage::disk('private')->put($originalPath, 'dummy content');

    $file = Asset::factory()->create([
        'file_name' => $originalName,
        'path'      => $originalPath,
    ]);

    $response = $this->postJson(route('admin.dam.assets.rename'), [
        'id'        => $file->id,
        'file_name' => $newName,
    ]);

    $response->assertStatus(200);

    $response->assertJson([
        'message' => trans('dam::app.admin.dam.index.directory.asset-renamed-success'),
    ]);

    $this->assertDatabaseHas('dam_assets', [
        'id'        => $file->id,
        'file_name' => $newName,
        'path'      => $newPath,
    ]);

    Storage::disk('private')->assertMissing($originalPath);

    Storage::disk('private')->assertExists($newPath);
});

// Move the Assets
it('should move asset from one directory to another', function () {
    Storage::fake('private');

    Storage::disk('private')->makeDirectory('assets/Root');
    Storage::disk('private')->makeDirectory('assets/Root/Screenshots');

    $rootDir = Directory::factory()->create(['name' => 'Root']);
    $newDirectory = Directory::factory()->create(['name' => 'Screenshots', 'parent_id' => $rootDir->id]);

    $asset = Asset::factory()->create([
        'file_name' => 'sample.jpg',
        'path'      => 'assets/Root/sample.jpg',
    ]);

    $asset->directories()->sync([$rootDir->id]);

    $response = $this->post(route('admin.dam.assets.moved'), [
        'move_item_id'  => $asset->id,
        'new_parent_id' => $newDirectory->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.asset-moved-success'),
        ]);

    $updatedAsset = Asset::find($asset->id);
    $this->assertEquals('assets/Root/Screenshots/sample.jpg', $updatedAsset->path);

    Storage::disk('private')->assertExists('assets/Root/Screenshots/sample.jpg');

    Storage::disk('private')->assertMissing('assets/Root/sample.jpg');
});
