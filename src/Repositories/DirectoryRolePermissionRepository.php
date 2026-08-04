<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Facades\DB;

class DirectoryRolePermissionRepository
{
    protected string $table = 'dam_directory_role';

    protected string $settingsTable = 'dam_role_settings';

    public function getAllGrantedIds(int $roleId): array
    {
        return DB::table($this->table)
            ->where('role_id', $roleId)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function getDirectoryIdsForRole(int $roleId): array
    {
        $json = DB::table($this->settingsTable)
            ->where('role_id', $roleId)
            ->value('explicit_directory_ids');

        if (! $json) {
            return [];
        }

        $ids = json_decode($json, true);

        return is_array($ids)
            ? array_values(array_map('intval', $ids))
            : [];
    }

    public function addDirectoryToRole(int $roleId, int $directoryId): void
    {
        $exists = DB::table($this->table)
            ->where('role_id', $roleId)
            ->where('directory_id', $directoryId)
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table($this->table)->insert([
            'directory_id' => $directoryId,
            'role_id'      => $roleId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    public function syncForRole(int $roleId, array $directoryIds): void
    {
        $newSubmitted = array_values(array_unique(array_map('intval', $directoryIds)));
        $prevExplicit = $this->getDirectoryIdsForRole($roleId);

        $existingInPivot = DB::table($this->table)
            ->where('role_id', $roleId)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $autoGranted = array_values(array_diff($existingInPivot, $prevExplicit));
        $newExplicit = array_values(array_diff($newSubmitted, $autoGranted));
        $toRemove = array_values(array_diff($prevExplicit, $newSubmitted));
        $toAdd = array_values(array_diff($newExplicit, $existingInPivot));

        DB::transaction(function () use ($roleId, $newExplicit, $toRemove, $toAdd) {
            if (! empty($toRemove)) {
                $removedAndDescendants = DB::table('dam_directories as ancestor')
                    ->join(
                        'dam_directories as descendant',
                        fn ($j) => $j
                            ->whereColumn('descendant._lft', '>=', 'ancestor._lft')
                            ->whereColumn('descendant._rgt', '<=', 'ancestor._rgt')
                    )
                    ->whereIn('ancestor.id', $toRemove)
                    ->whereIn('descendant.id', function ($sub) use ($roleId) {
                        $sub->from($this->table)
                            ->where('role_id', $roleId)
                            ->select('directory_id');
                    })
                    ->distinct()
                    ->pluck('descendant.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (! empty($removedAndDescendants)) {
                    DB::table($this->table)
                        ->where('role_id', $roleId)
                        ->whereIn('directory_id', $removedAndDescendants)
                        ->delete();
                }
            }

            if (! empty($toAdd)) {
                $now = now();
                $rows = array_map(fn ($id) => [
                    'directory_id' => $id,
                    'role_id'      => $roleId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ], $toAdd);

                DB::table($this->table)->insert($rows);
            }

            DB::table($this->settingsTable)
                ->where('role_id', $roleId)
                ->update(['explicit_directory_ids' => json_encode($newExplicit)]);
        });
    }
}
