<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetResourceMapping;
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
        'id' => $asset->id,
        'file_name' => 'updated-name.png',
        'tags' => ['tag1', 'tag2'],
    ];

    $this->put(route('admin.dam.assets.update', $asset->id), $updateData)
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.update-success'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), [
        'id' => $asset->id,
        'file_name' => 'updated-name.png',
    ]);
});

// Upload Asset File
it('should upload the asset file to the specified directory', function () {
    Storage::fake('private');
    Storage::disk('private')->makeDirectory('assets/New');

    $directory = Directory::factory()->create([
        'name' => 'New',
        'parent_id' => null,
    ]);

    $fileName = 'sample-'.uniqid().'.png';
    $file = UploadedFile::fake()->image($fileName, 600, 600)->size(23);
    $uploadData = [
        'files' => [$file],
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
        'path' => $uploadedPath,
    ]);
});

// Re-Upload Asset File
it('should re-upload the asset file to the specified directory and update the asset record', function () {
    Storage::fake('private');

    $directory = Directory::factory()->create(['name' => 'Root']);

    $originalFileName = 'original-'.uniqid().'.png';
    $initialFilePath = 'assets/Root/'.$originalFileName;
    $asset = Asset::factory()->create([
        'file_name' => $originalFileName,
        'path' => $initialFilePath,
    ]);

    $asset->directories()->attach($directory->id);

    Storage::disk('private')->put($initialFilePath, 'dummy content');

    $newFile = UploadedFile::fake()->image('sample.png', 600, 600)->size(23);

    $response = $this->postJson(route('admin.dam.assets.re_upload'), [
        'file' => $newFile,
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
        'id' => $asset->id,
        'file_name' => $newFileName,
        'path' => $expectedNewPath,
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

    $fileName = 'sample-'.uniqid().'.pdf';
    $filePath = 'assets/Root/'.$fileName;
    Storage::disk('private')->put($filePath, 'dummy content');

    $asset = Asset::factory()->create([
        'file_name' => $fileName,
        'path' => $filePath,
    ]);

    $response = $this->get(route('admin.dam.assets.download', $asset->id));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
});

// Custom Download Asset
it('should allow custom downloading of the asset', function () {
    $assetDisk = Directory::getAssetDisk();
    Storage::fake($assetDisk);

    $fileName = 'sample-'.uniqid().'.jpg';
    $file = UploadedFile::fake()->image($fileName, 600, 600)->size(23);
    Storage::disk($assetDisk)->putFileAs('assets/Root', $file, $fileName);

    $asset = Asset::factory()->create([
        'path' => 'assets/Root/'.$fileName,
        'file_name' => $fileName,
    ]);

    $response = $this->get(route('admin.dam.assets.custom_download', ['id' => $asset->id]).'?format=png');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});

// Rename File
it('should rename the file name', function () {
    Storage::fake('private');

    $originalName = 'original-name-'.uniqid().'.pdf';
    $newName = 'renamed-file-'.uniqid().'.pdf';

    $directory = 'uploads/assets/';
    $originalPath = $directory.$originalName;
    $newPath = $directory.$newName;

    Storage::disk('private')->put($originalPath, 'dummy content');

    $file = Asset::factory()->create([
        'file_name' => $originalName,
        'path' => $originalPath,
    ]);

    $response = $this->postJson(route('admin.dam.assets.rename'), [
        'id' => $file->id,
        'file_name' => $newName,
    ]);

    $response->assertStatus(200);

    $response->assertJson([
        'message' => trans('dam::app.admin.dam.index.directory.asset-renamed-success'),
    ]);

    $this->assertDatabaseHas('dam_assets', [
        'id' => $file->id,
        'file_name' => $newName,
        'path' => $newPath,
    ]);

    Storage::disk('private')->assertMissing($originalPath);

    Storage::disk('private')->assertExists($newPath);
});

// Upload forbidden file type
it('should reject uploading a forbidden file type', function () {
    Storage::fake('private');
    Storage::disk('private')->makeDirectory('assets/New');

    $directory = Directory::factory()->create([
        'name'      => 'New',
        'parent_id' => null,
    ]);

    $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

    $response = $this->postJson(route('admin.dam.assets.upload'), [
        'files'        => [$file],
        'directory_id' => $directory->id,
    ]);

    $response->assertStatus(500)
        ->assertJson(['success' => false]);
});

// Show non-existent asset
it('should return 404 when showing a non-existent asset', function () {
    $response = $this->getJson(route('admin.dam.assets.show', 99999));

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-show'),
        ]);
});

// Update non-existent asset
it('should return 404 when updating a non-existent asset', function () {
    $response = $this->putJson(route('admin.dam.assets.update', 99999), [
        'file_name' => 'test.png',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-update'),
        ]);
});

// Delete non-existent asset
it('should return 404 when deleting a non-existent asset', function () {
    $response = $this->deleteJson(route('admin.dam.assets.destroy', 99999));

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-destroy'),
        ]);
});

// Prevent delete when resource mapped
it('should prevent deletion of an asset that has linked resources', function () {
    $asset = Asset::factory()->create();

    AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    $response = $this->deleteJson(route('admin.dam.assets.destroy', $asset->id));

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => trans('dam::app.admin.dam.asset.delete-failed-due-to-attached-resources'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), ['id' => $asset->id]);
});

// Rename validation
it('should validate rename requires a valid file name', function () {
    $response = $this->postJson(route('admin.dam.assets.rename'), [
        'id'        => 1,
        'file_name' => '.hidden',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file_name']);
});

// Upload validation - missing files
it('should validate upload requires files and directory_id', function () {
    $response = $this->postJson(route('admin.dam.assets.upload'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['files', 'directory_id']);
});

// Move the Assets
it('should move asset from one directory to another', function () {
    Storage::fake('private');

    Storage::disk('private')->makeDirectory('assets/Root');
    Storage::disk('private')->makeDirectory('assets/Root/Screenshots');

    $rootDir = Directory::factory()->create(['name' => 'Root']);
    $newDirectory = Directory::factory()->create(['name' => 'Screenshots', 'parent_id' => $rootDir->id]);

    $fileName = 'sample-'.uniqid().'.jpg';
    $originalPath = 'assets/Root/'.$fileName;

    Storage::disk('private')->put($originalPath, 'dummy content');

    $asset = Asset::factory()->create([
        'file_name' => $fileName,
        'path' => $originalPath,
    ]);

    $asset->directories()->sync([$rootDir->id]);

    $response = $this->post(route('admin.dam.assets.moved'), [
        'move_item_id' => $asset->id,
        'new_parent_id' => $newDirectory->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => trans('dam::app.admin.dam.index.directory.asset-moved-success'),
        ]);

    $updatedAsset = Asset::find($asset->id);
    $expectedPath = 'assets/Root/Screenshots/'.$fileName;
    $this->assertEquals($expectedPath, $updatedAsset->path);

    Storage::disk('private')->assertExists($expectedPath);

    Storage::disk('private')->assertMissing($originalPath);
});
