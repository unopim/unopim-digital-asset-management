<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\DAM\Helpers\DamUpdater;

class DamUpdate extends Command
{
    protected $signature = 'dam:update {--skip-backup : Skip the pre-update backup} {--dry-run : Show the plan and exit}';

    protected $description = 'Safely apply an updated DAM version: backup, migrate, publish, verify.';

    public function handle(DamUpdater $updater): int
    {
        $before = $updater->countRows();

        if ($this->option('dry-run')) {
            $this->info('Dry run — would back up, migrate, publish, then verify. No changes made.');
            $this->line('Current row counts: '.json_encode($before));

            return self::SUCCESS;
        }

        if (! $this->option('skip-backup')) {
            $backup = $updater->backup(now()->format('Y-m-d_H-i-s'));
            $this->info('Backup created at: '.$backup['dir']);
        } else {
            $this->warn('--skip-backup set: no safety net.');
            if (! $this->confirm('Continue without a backup?', false)) {
                return self::SUCCESS;
            }
            $backup = null;
        }

        $this->info('Migrate...');
        $updater->runMigrations();

        $this->info('Publish...');
        $updater->publish();

        $updater->clearCaches();

        $this->info('Verify...');
        $result = $updater->verify($before);

        if (! $result['ok']) {
            $this->error('Row count dropped in: '.implode(', ', $result['dropped']));
            if ($backup) {
                $this->error('Restore with: php artisan dam:update:restore '.basename($backup['dir']));
            }

            return self::FAILURE;
        }

        $this->info('DAM updated successfully. No data lost.');
        if ($backup) {
            $this->line('Backup kept at: '.$backup['dir']);
        }

        return self::SUCCESS;
    }
}
