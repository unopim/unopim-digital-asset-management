<?php

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;

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

        $this->info('Unopim DAM package installed successfully!');
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
}
