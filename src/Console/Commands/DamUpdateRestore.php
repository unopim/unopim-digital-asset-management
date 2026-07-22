<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\DAM\Helpers\DamUpdater;

class DamUpdateRestore extends Command
{
    protected $signature = 'dam:update:restore {timestamp? : The backup folder name under storage/dam-backups}';

    protected $description = 'Restore DAM database tables and asset files from a backup.';

    public function handle(DamUpdater $updater): int
    {
        $timestamp = $this->argument('timestamp');

        if (! $timestamp) {
            $backups = $updater->listBackups();
            if ($backups === []) {
                $this->error('No backups found under storage/dam-backups.');

                return self::FAILURE;
            }
            $this->line('Available backups:');
            foreach ($backups as $b) {
                $this->line('  '.$b);
            }

            return self::SUCCESS;
        }

        if (! in_array($timestamp, $updater->listBackups(), true)) {
            $this->error("No backup found: {$timestamp}");

            return self::FAILURE;
        }

        $this->warn('This overwrites current DAM tables and asset files from the backup.');
        if (! $this->confirm('Continue?', false)) {
            return self::SUCCESS;
        }

        $updater->restore($timestamp);
        $this->info('Restore complete from: '.$timestamp);

        return self::SUCCESS;
    }
}
