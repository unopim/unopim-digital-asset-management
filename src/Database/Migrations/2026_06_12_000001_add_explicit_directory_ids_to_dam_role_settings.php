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
            $table->text('explicit_directory_ids')->nullable()->after('inherit_children');
        });

        $roles = DB::table('dam_role_settings')->pluck('role_id');

        foreach ($roles as $roleId) {
            $count = DB::table('dam_directory_role')->where('role_id', $roleId)->count();

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
