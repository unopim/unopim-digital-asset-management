<?php

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;

class DamInstaller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dam-package:install';

    protected $description = 'Install the Unopim DAM package';

    public function handle()
    {
        $this->info('Installing Unopim DAM...');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
            $this->call('db:seed', ['--class' => 'Webkul\DAM\Database\Seeders\DirectoryTableSeeder']);
        }

        $this->call('vendor:publish', [
            '--tag' => 'dam-config',
        ]);
        
        $this->info('Unopim DAM package installed successfully!');
    }
}
