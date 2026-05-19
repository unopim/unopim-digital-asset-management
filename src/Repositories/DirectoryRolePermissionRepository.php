<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Reads/writes the dam_directory_role pivot directly so the DAM package does not
 * have to add Eloquent relations on the Webkul/User Role model.
 */
class DirectoryRolePermissionRepository
{
    protected string $table = 'dam_directory_role';

    /**
     * Directory ids granted to the given role.
     *
     * @return array<int>
     */
    public function getDirectoryIdsForRole(int $roleId): array
    {
        return DB::table($this->table)
            ->where('role_id', $roleId)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Replace the granted directories for a role.
     *
     * @param  array<int>  $directoryIds
     */
    public function syncForRole(int $roleId, array $directoryIds): void
    {
        $directoryIds = array_values(array_unique(array_map('intval', $directoryIds)));

        DB::transaction(function () use ($roleId, $directoryIds) {
            DB::table($this->table)->where('role_id', $roleId)->delete();

            if (empty($directoryIds)) {
                return;
            }

            $now = now();

            $rows = array_map(fn ($id) => [
                'directory_id' => $id,
                'role_id'      => $roleId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ], $directoryIds);

            DB::table($this->table)->insert($rows);
        });
    }
}
