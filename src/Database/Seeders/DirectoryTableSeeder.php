<?php

namespace Webkul\DAM\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\DAM\Models\Directory;

class DirectoryTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $rootExists = Directory::where('name', 'Root')->whereNull('parent_id')->exists();

        if (! $rootExists) {
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
        }

        $newDirectory = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, 'Root');
        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($newDirectory)) {
            Storage::disk($disk)->makeDirectory($newDirectory);
        }

        if (! $rootExists) {
            $this->backfillRootGrants();
        }
    }

    /**
     * Grant the seeded Root directory to every existing custom role. Idempotent.
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
