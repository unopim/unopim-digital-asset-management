<?php

namespace Webkul\DAM\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\DAM\Models\Directory;

/*
 * Directory table seeder.
 */
class DirectoryTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        if (Directory::where('name', 'Root')->whereNull('parent_id')->exists()) {
            return;
        }

        DB::table('dam_directories')->insert([
            [
                '_lft'       => '1',
                '_rgt'       => '14',
                'name'       => 'Root',
                'parent_id'  => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DatabaseSequenceHelper::fixSequence('dam_directories');

        $newDirectory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, 'Root');
        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($newDirectory)) {
            Storage::disk($disk)->makeDirectory($newDirectory);
        }

        // Back-fill root grants for every existing custom role. Runs here
        // (in addition to the standalone back-fill migration) so fresh
        // installs — where the back-fill migration runs BEFORE this seeder
        // and finds no root — still get the grants created.
        $this->backfillRootGrants();
    }

    /**
     * Grant the seeded Root directory to every existing custom role so DAM
     * visibility is preserved out-of-the-box. Idempotent — skips rows that
     * already exist in the pivot.
     */
    protected function backfillRootGrants(): void
    {
        if (! Schema::hasTable('dam_directory_role')) {
            return;
        }

        $rootId = DB::table('dam_directories')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->value('id');

        if (! $rootId) {
            return;
        }

        $now = Carbon::now();

        $roleIds = DB::table('roles')
            ->where('permission_type', 'custom')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('dam_directory_role')
                ->where('directory_id', $rootId)
                ->where('role_id', $roleId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('dam_directory_role')->insert([
                'directory_id' => $rootId,
                'role_id'      => $roleId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
