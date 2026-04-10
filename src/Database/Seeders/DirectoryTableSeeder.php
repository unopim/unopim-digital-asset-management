<?php

namespace Webkul\DAM\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
    }
}
