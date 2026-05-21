<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_shares', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('revoked_at');
            $table->index(['share_type', 'target_id', 'is_active'], 'dam_shares_target_active_idx');
        });

        DB::table('dam_shares')
            ->whereNotNull('revoked_at')
            ->update(['is_active' => false]);

        $duplicates = DB::table('dam_shares')
            ->select('share_type', 'target_id')
            ->where('is_active', true)
            ->groupBy('share_type', 'target_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $keepId = DB::table('dam_shares')
                ->where('share_type', $duplicate->share_type)
                ->where('target_id', $duplicate->target_id)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->value('id');

            DB::table('dam_shares')
                ->where('share_type', $duplicate->share_type)
                ->where('target_id', $duplicate->target_id)
                ->where('is_active', true)
                ->where('id', '!=', $keepId)
                ->update([
                    'is_active'  => false,
                    'revoked_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('dam_shares', function (Blueprint $table) {
            $table->dropIndex('dam_shares_target_active_idx');
            $table->dropColumn('is_active');
        });
    }
};
