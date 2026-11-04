<?php

declare(strict_types=1);

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardStats
{
    protected const CACHE_TTL = 300;

    protected const CACHE_KEY = 'dam.dashboard.stats';

    protected const TYPES = ['image', 'video', 'audio', 'document'];

    public static function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{
     *     totals: array<string, int>,
     *     types: array<int, array{type: string, count: int, size: int}>,
     *     recent: array<int, object>
     * }
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $byType = DB::table('dam_assets')
                ->select('file_type', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(file_size), 0) as size'))
                ->groupBy('file_type')
                ->get()
                ->keyBy('file_type');

            $totals = [
                'assets'      => (int) DB::table('dam_assets')->count(),
                'storage'     => (int) DB::table('dam_assets')->sum('file_size'),
                'directories' => (int) DB::table('dam_directories')->whereNotNull('parent_id')->count(),
                'tags'        => (int) DB::table('dam_tags')->count(),
                'shares'      => Schema::hasTable('dam_shares') ? (int) DB::table('dam_shares')->count() : 0,
            ];

            $types = array_map(fn (string $type): array => [
                'type'  => $type,
                'count' => (int) ($byType[$type]->count ?? 0),
                'size'  => (int) ($byType[$type]->size ?? 0),
            ], self::TYPES);

            $recent = DB::table('dam_assets')
                ->select('id', 'file_name', 'file_type', 'file_size', 'extension', 'created_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->all();

            return compact('totals', 'types', 'recent');
        });
    }

    public static function humanBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max(0.0, (float) $bytes);

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return round($value, $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
