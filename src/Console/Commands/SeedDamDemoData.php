<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\DAM\Helpers\DamDemoDataInstaller;

use function Laravel\Prompts\confirm;

class SeedDamDemoData extends Command
{
    protected $signature = 'dam:demo-data
        { --force : Re-seed even when demo data is already present. }';

    protected $description = 'Seed demo directories and assets into the DAM module.';

    public function handle(DamDemoDataInstaller $installer): int
    {
        if ($this->option('force')) {
            $this->warn('--force will delete ALL assets stored under assets/Root/, including any user-added files.');

            if (! confirm('This will permanently delete all existing DAM assets. Continue?', false)) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $result = $installer->seed(
            fn (string $message) => $this->warn('Step: '.$message),
            (bool) $this->option('force'),
        );

        if (! ($result['success'] ?? false)) {
            $this->error("Failed to seed DAM demo data: {$result['error']}");

            return self::FAILURE;
        }

        if ($result['skipped'] ?? false) {
            $this->info('DAM demo data already present — nothing to do. Re-run with --force to re-seed.');

            return self::SUCCESS;
        }

        $this->info('DAM demo data seeded successfully.');

        return self::SUCCESS;
    }
}
