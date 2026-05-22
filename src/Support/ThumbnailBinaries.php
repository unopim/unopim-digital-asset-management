<?php

namespace Webkul\DAM\Support;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Resolve the absolute path to ffmpeg / pdftoppm binaries.
 *
 * Web-request processes (artisan serve, php-fpm) often inherit a restricted
 * $PATH that doesn't include common install locations like $HOME/bin or
 * /opt/homebrew/bin. The Symfony Process loader calls execvp which then
 * fails with "exec: ffmpeg: not found" even though `which ffmpeg` works in
 * the shell.
 *
 * Resolution order:
 *   1. Configured override (env: FFMPEG_PATH / PDFTOPPM_PATH) — explicit wins.
 *   2. Symfony's ExecutableFinder over $PATH + a list of common dirs.
 *   3. Bare command name as a last-resort fallback (matches legacy behavior
 *      so call sites still get a useful error message if everything fails).
 *
 * Cached per-request in a static so we only pay the filesystem-search cost
 * once per binary per HTTP request.
 */
class ThumbnailBinaries
{
    /** @var array<string,string> */
    private static array $cache = [];

    public static function ffmpeg(): string
    {
        return static::resolve('ffmpeg', env('FFMPEG_PATH'));
    }

    public static function pdftoppm(): string
    {
        return static::resolve('pdftoppm', env('PDFTOPPM_PATH'));
    }

    private static function resolve(string $name, ?string $override): string
    {
        if ($override && is_executable($override)) {
            return $override;
        }

        if (isset(static::$cache[$name])) {
            return static::$cache[$name];
        }

        $finder = new ExecutableFinder;
        $extraDirs = array_filter([
            '/usr/local/bin',
            '/usr/bin',
            '/opt/homebrew/bin',
            getenv('HOME') ? getenv('HOME').'/bin' : null,
            getenv('HOME') ? getenv('HOME').'/.local/bin' : null,
        ]);

        $resolved = $finder->find($name, $name, $extraDirs);

        return static::$cache[$name] = $resolved;
    }
}
