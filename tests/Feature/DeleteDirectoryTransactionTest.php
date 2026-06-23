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

    // First directory becomes the non-deletable root; we delete a child subtree of it.
    $parent = Directory::create(['name' => 'parent']);
    $target = Directory::create(['name' => 'target', 'parent_id' => $parent->id]);
    $child = Directory::create(['name' => 'child', 'parent_id' => $target->id]);

    // The job's writable guard calls is_writable() on the parent's real storage path,
    // so the fake disk must actually contain those directories.
    foreach ([$parent, $target, $child] as $directory) {
        Storage::disk($disk)->makeDirectory(Directory::ASSETS_DIRECTORY.'/'.$directory->generatePath());
    }

    $assetA = Asset::factory()->create();
    $assetB = Asset::factory()->create();
    $assetA->directories()->attach($target->id);
    $assetB->directories()->attach($child->id);

    // Run the job through its real handle() (queue is sync in tests).
    (new DeleteDirectory($target->id, $this->admin->id))->handle();

    // The whole subtree is gone...
    $this->assertDatabaseMissing('dam_directories', ['id' => $target->id]);
    $this->assertDatabaseMissing('dam_directories', ['id' => $child->id]);

    // ...along with every asset it contained...
    $this->assertDatabaseMissing('dam_assets', ['id' => $assetA->id]);
    $this->assertDatabaseMissing('dam_assets', ['id' => $assetB->id]);

    // ...and there are no orphaned pivot rows pointing at the deleted directories.
    $this->assertDatabaseMissing('dam_asset_directory', ['directory_id' => $target->id]);
    $this->assertDatabaseMissing('dam_asset_directory', ['directory_id' => $child->id]);

    // The parent (outside the deleted subtree) survives untouched.
    $this->assertDatabaseHas('dam_directories', ['id' => $parent->id]);
});
