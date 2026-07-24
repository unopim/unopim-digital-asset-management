<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\DeleteDirectory;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->admin = $this->loginAsAdmin();
});

it('removes a directory subtree, its assets and pivots atomically, leaving no orphans', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $parent = Directory::create(['name' => 'parent']);
    $target = Directory::create(['name' => 'target', 'parent_id' => $parent->id]);
    $child = Directory::create(['name' => 'child', 'parent_id' => $target->id]);

    foreach ([$parent, $target, $child] as $directory) {
        Storage::disk($disk)->makeDirectory(Directory::ASSETS_DIRECTORY.'/'.$directory->generatePath());
    }

    $assetA = Asset::factory()->create();
    $assetB = Asset::factory()->create();
    $assetA->directories()->attach($target->id);
    $assetB->directories()->attach($child->id);

    (new DeleteDirectory($target->id, $this->admin->id))->handle();

    $this->assertDatabaseMissing('dam_directories', ['id' => $target->id]);
    $this->assertDatabaseMissing('dam_directories', ['id' => $child->id]);

    $this->assertDatabaseMissing('dam_assets', ['id' => $assetA->id]);
    $this->assertDatabaseMissing('dam_assets', ['id' => $assetB->id]);

    $this->assertDatabaseMissing('dam_asset_directory', ['directory_id' => $target->id]);
    $this->assertDatabaseMissing('dam_asset_directory', ['directory_id' => $child->id]);

    $this->assertDatabaseHas('dam_directories', ['id' => $parent->id]);
});
