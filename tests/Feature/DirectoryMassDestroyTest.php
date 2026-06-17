<?php

use Illuminate\Support\Facades\Bus;
use Webkul\DAM\Jobs\DeleteDirectory;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('mass destroys multiple accessible deletable directories', function () {
    Bus::fake();

    $dir1 = Directory::factory()->create(['name' => 'FolderA', 'parent_id' => null]);
    $dir2 = Directory::factory()->create(['name' => 'FolderB', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.directory.mass_destroy'), [
        'indices' => [$dir1->id, $dir2->id],
    ]);

    $response->assertOk();
    $response->assertJsonFragment([
        'message' => trans('dam::app.admin.dam.index.directory.deleting-in-progress'),
    ]);

    Bus::assertDispatchedTimes(DeleteDirectory::class, 2);
});

it('skips root directory (id=1) in mass destroy', function () {
    Bus::fake();

    $root = Directory::find(1);
    if (! $root) {
        $root = Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    }

    $normal = Directory::factory()->create(['name' => 'Normal', 'parent_id' => null]);

    $response = $this->postJson(route('admin.dam.directory.mass_destroy'), [
        'indices' => [$root->id, $normal->id],
    ]);

    $response->assertOk();
    // Only the normal directory gets a job; root is skipped via isDeletable()
    Bus::assertDispatchedTimes(DeleteDirectory::class, 1);
});

it('returns 422 when indices is missing', function () {
    $response = $this->postJson(route('admin.dam.directory.mass_destroy'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['indices']);
});

it('returns 422 when indices contains non-integer values', function () {
    $response = $this->postJson(route('admin.dam.directory.mass_destroy'), [
        'indices' => ['not-an-int', 'also-not'],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['indices.0', 'indices.1']);
});
