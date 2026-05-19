<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('dam_directories')) {
            return;
        }

        $rootId = DB::table('dam_directories')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->value('id');

        if (! $rootId) {
            return;
        }

        $now = now();

        $roleIds = DB::table('roles')
            ->where('permission_type', 'custom')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('dam_directory_role')
                ->where('directory_id', $rootId)
                ->where('role_id', $roleId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('dam_directory_role')->insert([
                'directory_id' => $rootId,
                'role_id'      => $roleId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        // no-op — pivot rows are removed by table drop in the prior migration's down()
    }
};
