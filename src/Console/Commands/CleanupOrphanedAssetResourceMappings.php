<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Jobs\CleanupOrphanedAssetResourceMappings as CleanupJob;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class CleanupOrphanedAssetResourceMappings extends Command
{
    protected $signature = 'dam:cleanup-orphaned-asset-mappings {--sync : Run synchronously instead of dispatching to the queue}';

    protected $description = 'Drop category/product asset mappings left over from deleted category fields or attributes, which block those assets from being deleted.';

    public function handle(): int
    {
        if ($this->option('sync')) {
            (new CleanupJob)->handle(
                app(CategoryFieldRepository::class),
                app(AttributeRepository::class),
                app(AssetResourceMappingRepository::class)
            );

            $this->info('Orphaned asset-resource mappings cleaned up.');

            return self::SUCCESS;
        }

        CleanupJob::dispatch();

        $this->info('Cleanup job queued.');
        $this->line('Make sure a queue worker is running (e.g. `php artisan queue:work`).');

        return self::SUCCESS;
    }
}
