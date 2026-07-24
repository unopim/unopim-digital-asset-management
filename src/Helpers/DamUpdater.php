<?php

declare(strict_types=1);

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Database\Seeders\DirectoryTableSeeder;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Support\DamTables;

class DamUpdater
{
    public const TRACKED = ['dam_assets', 'dam_directories', 'dam_tags'];

    public function countRows(): array
    {
        $counts = [];
        foreach (self::TRACKED as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    public function verify(array $before): array
    {
        $after = $this->countRows();
        $dropped = [];

        foreach ($before as $table => $count) {
            if (($after[$table] ?? 0) < $count) {
                $dropped[] = $table;
            }
        }

        return ['ok' => $dropped === [], 'before' => $before, 'after' => $after, 'dropped' => $dropped];
    }

    public function backupDir(string $timestamp): string
    {
        $base = storage_path('dam-backups/'.$timestamp);
        $dir = $base;
        $i = 1;
        while (! @mkdir($dir, 0755, true)) {
            if (! is_dir($dir)) {
                throw new \RuntimeException("Cannot create backup directory: {$dir}");
            }
            $dir = $base.'-'.$i++;
        }

        return $dir;
    }

    private function existingTables(): array
    {
        $tables = array_filter(DamTables::ALL, fn ($t) => Schema::hasTable($t));

        $migrations = $this->migrationsTable();

        if (Schema::hasTable($migrations)) {
            $tables[] = $migrations;
        }

        return array_values($tables);
    }

    private function migrationsTable(): string
    {
        $migrations = config('database.migrations', 'migrations');

        if (is_array($migrations)) {
            return (string) ($migrations['table'] ?? 'migrations');
        }

        return (string) $migrations;
    }

    public function buildDumpCommand(string $sqlPath, ?array $tables = null): array
    {
        $tables ??= DamTables::ALL;

        if ($tables === []) {
            throw new \RuntimeException('Refusing to dump: no DAM tables resolved, which would export the entire database.');
        }

        $prefix = DB::getTablePrefix();

        if ($prefix !== '') {
            $tables = array_map(fn ($t) => $prefix.$t, $tables);
        }

        $conn = config('database.connections.'.config('database.default'));
        $driver = $conn['driver'] ?? 'mysql';

        if ($driver === 'pgsql') {

            return array_merge(
                ['pg_dump', '-c', '--if-exists', '-h', (string) $conn['host'], '-p', (string) $conn['port'],
                    '-U', (string) $conn['username'], '-d', (string) $conn['database'], '-f', $sqlPath],
                $this->flag('-t', $tables),
            );
        }

        return array_merge(
            ['mysqldump', '-h'.$conn['host'], '-P'.$conn['port'], '-u'.$conn['username'], $conn['database']],
            $tables,
            ['-r', $sqlPath],
        );
    }

    private function flag(string $flag, array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            $out[] = $flag;
            $out[] = $v;
        }

        return $out;
    }

    public function archiveAssetFiles(string $backupDir): ?string
    {
        $disk = Directory::getAssetDisk();

        if ($disk === Directory::ASSETS_DISK_AWS) {
            return null;
        }

        if (! Storage::disk($disk)->exists(Directory::ASSETS_DIRECTORY)) {
            return null;
        }

        $root = Storage::disk($disk)->path('');
        $tgz = $backupDir.'/dam-files.tgz';

        $result = Process::timeout(0)->run(['tar', '-czf', $tgz, '-C', $root, Directory::ASSETS_DIRECTORY]);
        if (! $result->successful()) {
            throw new \RuntimeException('Asset file backup failed: '.$result->errorOutput());
        }

        return $tgz;
    }

    public function backup(string $timestamp): array
    {
        $dir = $this->backupDir($timestamp);
        $sql = $dir.'/dam-tables.sql';

        $result = Process::env($this->dbEnv())->timeout(0)->run($this->buildDumpCommand($sql, $this->existingTables()));
        if (! $result->successful()) {
            throw new \RuntimeException('Database dump failed: '.$result->errorOutput());
        }

        return ['dir' => $dir, 'sql' => $sql, 'files' => $this->archiveAssetFiles($dir)];
    }

    public function runMigrations(): void
    {
        Artisan::call('migrate', ['--force' => true]);
    }

    public function publish(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'dam-config', '--force' => true]);
        Artisan::call('vendor:publish', ['--tag' => 'dam-defaults']);
        Artisan::call('db:seed', ['--class' => DirectoryTableSeeder::class, '--force' => true]);
    }

    public function clearCaches(): void
    {
        Artisan::call('optimize:clear');
    }

    private function dbEnv(): array
    {
        $conn = config('database.connections.'.config('database.default'));
        $driver = $conn['driver'] ?? 'mysql';
        $pass = (string) ($conn['password'] ?? '');

        return $driver === 'pgsql' ? ['PGPASSWORD' => $pass] : ['MYSQL_PWD' => $pass];
    }

    public function listBackups(): array
    {
        $base = storage_path('dam-backups');
        if (! is_dir($base)) {
            return [];
        }
        $dirs = array_values(array_filter(
            scandir($base),
            fn ($d) => $d !== '.' && $d !== '..' && is_dir($base.'/'.$d),
        ));
        rsort($dirs);

        return $dirs;
    }

    public function restore(string $timestamp): void
    {

        if (! preg_match('/^[\w-]+$/', $timestamp)) {
            throw new \RuntimeException("Invalid backup name: {$timestamp}");
        }

        $dir = storage_path('dam-backups/'.$timestamp);
        if (! is_dir($dir)) {
            throw new \RuntimeException("No backup found: {$timestamp}");
        }

        $sql = $dir.'/dam-tables.sql';
        if (is_file($sql)) {
            $conn = config('database.connections.'.config('database.default'));
            $driver = $conn['driver'] ?? 'mysql';

            if ($driver === 'pgsql') {

                $cmd = ['psql', '-v', 'ON_ERROR_STOP=1', '-h', (string) $conn['host'], '-p', (string) $conn['port'],
                    '-U', (string) $conn['username'], '-d', (string) $conn['database'], '-f', $sql];
                $r = Process::env($this->dbEnv())->timeout(0)->run($cmd);
            } else {

                $cmd = ['mysql', '-h'.$conn['host'], '-P'.$conn['port'], '-u'.$conn['username'], $conn['database']];
                $r = Process::env($this->dbEnv())->timeout(0)->input(fopen($sql, 'r'))->run($cmd);
            }

            if (! $r->successful()) {
                throw new \RuntimeException('DB restore failed: '.$r->errorOutput());
            }
        }

        $tgz = $dir.'/dam-files.tgz';
        if (is_file($tgz)) {
            $root = Storage::disk(Directory::getAssetDisk())->path('');
            $r = Process::timeout(0)->run(['tar', '-xzf', $tgz, '-C', $root]);
            if (! $r->successful()) {
                throw new \RuntimeException('Asset file restore failed: '.$r->errorOutput());
            }
        }
    }
}
