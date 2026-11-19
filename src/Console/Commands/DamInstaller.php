<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Core\Platform\ModuleGate;
use Webkul\DAM\Helpers\DamDemoDataInstaller;
use Webkul\Installer\Helpers\PlatformModules;

use function Laravel\Prompts\confirm;

class DamInstaller extends Command
{
    protected $signature = 'dam-package:install';

    protected $description = 'Install the Unopim DAM package';

    public function handle()
    {
        $this->info('Installing Unopim DAM...');

        $this->runMigrations();

        $this->publishAssets([
            'dam-config',
            'dam-defaults',
        ]);

        $this->enablePlatformModule();

        $this->info('Unopim DAM package installed successfully!');

        if (confirm('Would you like to seed demo data (sample directories and assets)?', false)) {
            $this->seedDemoData();
        }
    }

    protected function runMigrations(): void
    {
        if (! $this->confirm('Would you like to run the migrations now?', true)) {
            return;
        }

        $this->call('migrate');
        $this->call('db:seed', [
            '--class' => 'Webkul\DAM\Database\Seeders\DirectoryTableSeeder',
        ]);
    }

    protected function publishAssets(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->call('vendor:publish', ['--tag' => $tag, '--force' => true]);
        }
    }

    /**
     * Add the DAM module to the platform selection so its menus and routes
     * become visible, preserving any already-enabled modules (e.g. PIM).
     */
    protected function enablePlatformModule(): void
    {
        try {
            $current = app(ModuleGate::class)->modules();

            if (in_array('dam', $current, true)) {
                return;
            }

            $modules = array_values(array_unique([...$current, 'dam']));

            app(PlatformModules::class)->store($modules);

            $this->info('DAM enabled in platform modules ['.implode(', ', $modules).'].');
        } catch (\Throwable $e) {
            $this->warn('Could not update platform modules automatically: '.$e->getMessage());
            $this->warn('Enable DAM manually under Configure → General → Platform.');
        }
    }

    protected function seedDemoData(): void
    {
        $result = app(DamDemoDataInstaller::class)
            ->seed(fn (string $message) => $this->warn('Step: '.$message));

        if (! ($result['success'] ?? false)) {
            $this->error("Failed to seed DAM demo data: {$result['error']}");

            return;
        }

        if ($result['skipped'] ?? false) {
            $this->info('DAM demo data already present — skipping.');

            return;
        }

        $this->info('DAM demo data seeded successfully.');
    }
}
