<?php

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkul\User\Models\Admin;

class MoveDamAssetsToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:dam:move-asset-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move DAM asset files to AWS S3 if they exist locally';

    /**
     * The total no of records move at a time
     *
     * @var int
     */
    protected $limit = 1000;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->authCheck();
    }

    /**
     * To Check user Authenticity
     */
    public function authCheck()
    {
        $email = $this->ask('Enter your Email');
        $password = $this->secret('Enter your Password');
        $migrateNew = false;
        $migrateNewRes = $this->ask('Want to migrate only new uploaded files from your local to s3 (yes/no)');

        if (in_array(strtolower($migrateNewRes), ['yes', 'y'])) {
            $migrateNew = true;
        }

        $this->info('Migrate New only flag: '.($migrateNew ? 'Yes' : 'No'));

        $delete = false;
        $userRes = $this->ask('Want to delete files from local once uploaded to s3? (yes/no)');

        if (in_array(strtolower($userRes), ['yes', 'y'])) {
            $delete = true;
        }

        $this->info('Delete flag: '.($delete ? 'Yes' : 'No'));

        $admin = Admin::where('email', $email)->first();

        if ($admin && Hash::check($password, $admin->password)) {
            $this->moveAssetsTos3($delete, $migrateNew);
        } else {
            $this->info('Access Denied : Invalid Credentials.');
        }
    }

    /**
     * Move Assets to s3 from private disk
     */
    public function moveAssetsTos3(bool $delete, bool $migrateNew)
    {
        $offset = 0;
        $this->info('Starting migration to aws');

        $totalAssetCount = DB::table('dam_assets')->count();
        $progressBar = new ProgressBar($this->output, $totalAssetCount);
        $progressBar->start();
        $logs = [];

        do {
            $assets = DB::table('dam_assets')
                ->limit($this->limit)
                ->offset($offset)
                ->get();

            if ($assets->isEmpty()) {
                break;
            }

            foreach ($assets as $asset) {
                $filePath = $asset->path ?? null;
                if (! $filePath) {
                    $logs[] = "No file Path for asset ID: ($asset->id)";
                    $progressBar->advance();

                    continue;
                }

                if ($migrateNew) {
                    $this->migrateNew($filePath, $delete, $asset, $logs);
                } else {
                    $this->migrateAll($filePath, $delete, $asset, $logs);
                }

                $progressBar->advance();
            }
            $offset += $this->limit;
        } while ($assets->count() === $this->limit);
        $progressBar->finish();
        $this->newLine();
        if ($delete) {
            $this->info('Files Deleted from your local Private Disk Successfully!!.');
        }
        Log::info('User data: '.json_encode($logs));
        $this->info('Done Moving DAM Assets.');
    }

    /**
     * Migrate all Assets to s3
     */
    public function migrateAll(string $filePath, bool $delete, $asset, array &$logs)
    {
        if (Storage::disk('private')->exists($filePath)) {
            $fileContents = Storage::disk('private')->get($filePath);

            Storage::disk('s3')->put($filePath, $fileContents);
            if ($delete) {
                $previewPath = 'preview/1356/'.$filePath;
                Storage::disk('private')->delete($filePath);
                Storage::disk('private')->delete($previewPath);
                Storage::disk('private')->delete('thumbnails/'.$filePath);
            }
            $logs[] = "Moved File for asset ID {$asset->id} to s3";
        } else {
            $logs[] = "File not Found for asset Path {$asset->path}: $filePath";
        }
    }

    /**
     * Migrate only new Assets to s3
     */
    public function migrateNew(string $filePath, bool $delete, $asset, array &$logs)
    {
        if (Storage::disk('private')->exists($filePath)) {
            if (! Storage::disk('s3')->exists($filePath)) {
                $fileContents = Storage::disk('private')->get($filePath);
                Storage::disk('s3')->put($filePath, $fileContents);
                if ($delete) {
                    $previewPath = 'preview/1356/'.$filePath;
                    Storage::disk('private')->delete($filePath);
                    Storage::disk('private')->delete($previewPath);
                    Storage::disk('private')->delete('thumbnails/'.$filePath);
                }
                $logs[] = "Moved File for asset ID {$asset->id} to s3";
            } else {
                $logs[] = "File for asset ID {$asset->id} already exists on S3. Skipped.";
            }
        } else {
            $logs[] = "File not Found for asset Path {$asset->path}: $filePath";
        }
    }
}
