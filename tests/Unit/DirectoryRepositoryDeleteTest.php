<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;

it('returns the delete result and removes the directory from storage', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $root = Directory::factory()->create(['name' => 'DeleteRoot']);
    $directory = Directory::factory()->create(['name' => 'Obsolete', 'parent_id' => $root->id]);

    Storage::disk($disk)->makeDirectory('assets/DeleteRoot/Obsolete');

    $result = app(DirectoryRepository::class)->delete($directory->id);

    expect($result)->toBeTruthy()
        ->and(Directory::find($directory->id))->toBeNull();

    Storage::disk($disk)->assertMissing('assets/DeleteRoot/Obsolete');
});
