<?php

declare(strict_types=1);

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;

class DamVersion extends Command
{
    protected $signature = 'dam:version';

    protected $description = 'Print the installed Unopim DAM version.';

    public function handle(): int
    {
        $this->line($this->resolveVersion());

        return self::SUCCESS;
    }

    public static function resolveVersion(): string
    {
        $changelog = dirname(__DIR__, 3).'/Changelog.md';

        if (is_readable($changelog)
            && preg_match('/Version\s+([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i', (string) file_get_contents($changelog), $m)) {
            return $m[1];
        }

        return '1.0.0';
    }
}
