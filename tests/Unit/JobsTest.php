<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\CopyDirectoryStructure;
use Webkul\DAM\Jobs\DeleteDirectory;
use Webkul\DAM\Jobs\MoveDirectoryStructure;
use Webkul\DAM\Jobs\RenameDirectory;
use Webkul\DAM\Models\ActionRequest;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

/**
 * Helper to create a pending ActionRequest for the current user.
 */
function createPendingActionRequest(string $eventType): void
{
    ActionRequest::create([
        'event_type' => $eventType,
        'status'     => 'pending',
        'admin_id'   => auth()->id(),
    ]);
}

// DeleteDirectory Job Tests

it('dispatches delete directory job', function () {
    Bus::fake();

    $directory = Directory::factory()->create();

    DeleteDirectory::dispatch($directory->id, auth()->id());

    Bus::assertDispatched(DeleteDirectory::class);
});

it('delete directory job is queueable', function () {
    Queue::fake();

    $directory = Directory::factory()->create();

    DeleteDirectory::dispatch($directory->id, auth()->id());

    Queue::assertPushed(DeleteDirectory::class);
});

it('delete directory job deletes directory and its assets', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $parent = Directory::factory()->create(['name' => 'Parent']);
    $directory = Directory::factory()->create(['name' => 'ToDelete', 'parent_id' => $parent->id]);

    $asset = Asset::factory()->create([
        'file_name' => 'test.jpg',
        'path'      => 'assets/Parent/ToDelete/test.jpg',
    ]);
    $directory->assets()->attach($asset->id);

    Storage::disk($disk)->put('assets/Parent/ToDelete/test.jpg', 'content');
    Storage::disk($disk)->makeDirectory('assets/Parent/ToDelete');

    $directoryId = $directory->id;

    createPendingActionRequest('delete_directory');

    $job = new DeleteDirectory($directoryId, auth()->id());
    $job->handle();

    $this->assertDatabaseMissing('dam_directories', ['id' => $directoryId]);
});

// RenameDirectory Job Tests

it('dispatches rename directory job', function () {
    Bus::fake();

    $directory = Directory::factory()->create();

    RenameDirectory::dispatch($directory->id, auth()->id());

    Bus::assertDispatched(RenameDirectory::class);
});

it('rename directory job is queueable', function () {
    Queue::fake();

    $directory = Directory::factory()->create();

    RenameDirectory::dispatch($directory->id, auth()->id());

    Queue::assertPushed(RenameDirectory::class);
});

it('rename directory job updates asset paths', function () {
    Storage::fake(Directory::getAssetDisk());

    $parent = Directory::factory()->create(['name' => 'Root']);
    $directory = Directory::factory()->create(['name' => 'NewName', 'parent_id' => $parent->id]);

    $asset = Asset::factory()->create([
        'file_name' => 'photo.jpg',
        'path'      => 'assets/Root/OldName/photo.jpg',
    ]);
    $directory->assets()->attach($asset->id);

    createPendingActionRequest('rename_directory');

    $job = new RenameDirectory($directory->id, auth()->id());
    $job->handle();

    $asset->refresh();
    $expectedPath = sprintf('assets/%s/photo.jpg', $directory->generatePath());
    expect($asset->path)->toBe($expectedPath);
});

// CopyDirectoryStructure Job Tests

it('dispatches copy directory structure job', function () {
    Bus::fake();

    $directory = Directory::factory()->create();

    CopyDirectoryStructure::dispatch($directory->id, auth()->id());

    Bus::assertDispatched(CopyDirectoryStructure::class);
});

it('copy directory structure job is queueable', function () {
    Queue::fake();

    $directory = Directory::factory()->create();

    CopyDirectoryStructure::dispatch($directory->id, auth()->id());

    Queue::assertPushed(CopyDirectoryStructure::class);
});

it('copy directory structure creates a new directory with copy suffix', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $parent = Directory::factory()->create(['name' => 'Root']);
    $directory = Directory::factory()->create(['name' => 'Original', 'parent_id' => $parent->id]);

    Storage::disk($disk)->makeDirectory('assets/Root/Original');

    createPendingActionRequest('copy_directory_structure');

    $job = new CopyDirectoryStructure($directory->id, auth()->id());
    $job->handle();

    $this->assertDatabaseHas('dam_directories', [
        'name'      => 'Original 1th copy',
        'parent_id' => $parent->id,
    ]);
});

// MoveDirectoryStructure Job Tests

it('dispatches move directory structure job', function () {
    Bus::fake();

    $source = Directory::factory()->create();
    $target = Directory::factory()->create();

    MoveDirectoryStructure::dispatch($source->id, $target->id, auth()->id());

    Bus::assertDispatched(MoveDirectoryStructure::class);
});

it('move directory structure job changes parent', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $root = Directory::factory()->create(['name' => 'Root']);
    $source = Directory::factory()->create(['name' => 'Source', 'parent_id' => $root->id]);
    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => $root->id]);

    Storage::disk($disk)->makeDirectory('assets/Root/Source');
    Storage::disk($disk)->makeDirectory('assets/Root/Target');

    createPendingActionRequest('move_directory_structure');

    $job = new MoveDirectoryStructure($source->id, $target->id, auth()->id());
    $job->handle();

    $source->refresh();
    expect($source->parent_id)->toBe($target->id);
});

it('move directory structure job updates asset paths', function () {
    $disk = Directory::getAssetDisk();
    Storage::fake($disk);

    $root = Directory::factory()->create(['name' => 'Root']);
    $source = Directory::factory()->create(['name' => 'Source', 'parent_id' => $root->id]);
    $target = Directory::factory()->create(['name' => 'Target', 'parent_id' => $root->id]);

    $asset = Asset::factory()->create([
        'file_name' => 'file.jpg',
        'path'      => 'assets/Root/Source/file.jpg',
    ]);
    $source->assets()->attach($asset->id);

    Storage::disk($disk)->makeDirectory('assets/Root/Source');
    Storage::disk($disk)->makeDirectory('assets/Root/Target');
    Storage::disk($disk)->put('assets/Root/Source/file.jpg', 'content');

    createPendingActionRequest('move_directory_structure');

    $job = new MoveDirectoryStructure($source->id, $target->id, auth()->id());
    $job->handle();

    $asset->refresh();
    expect($asset->path)->toContain('Target');
});
