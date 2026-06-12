<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_role_settings', function (Blueprint $table) {
            // JSON-encoded array of admin-selected explicit directory IDs.
            // Small by design — only the roots the admin chose in the editor.
            // Populated below for existing roles; roles with > 100 grants are
            // assumed to have been expanded by the old SyncDirectoryGrants job
            // and are reset to [] (admin must re-save to restore their selection).
            $table->text('explicit_directory_ids')->nullable()->after('inherit_children');
        });

        // Backfill for existing roles.
        $roles = DB::table('dam_role_settings')->pluck('role_id');

        foreach ($roles as $roleId) {
            $count = DB::table('dam_directory_role')->where('role_id', $roleId)->count();

            // Roles with > 100 grants almost certainly had job-expansion applied.
            // Reset to empty so the admin re-selects on next save.
            $ids = $count <= 100
                ? DB::table('dam_directory_role')
                    ->where('role_id', $roleId)
                    ->pluck('directory_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all()
                : [];

            DB::table('dam_role_settings')
                ->where('role_id', $roleId)
                ->update(['explicit_directory_ids' => json_encode($ids)]);
        }
    }

    public function down(): void
    {
        Schema::table('dam_role_settings', function (Blueprint $table) {
            $table->dropColumn('explicit_directory_ids');
        });
    }
};
