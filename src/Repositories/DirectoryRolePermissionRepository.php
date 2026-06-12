<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Reads/writes the dam_directory_role pivot directly so the DAM package does not
 * have to add Eloquent relations on the Webkul/User Role model.
 *
 * Two storage layers:
 *   dam_role_settings.explicit_directory_ids — JSON array of admin-selected roots.
 *       Small by design; used only by the permission editor view composer.
 *   dam_directory_role — ALL granted directories: explicit + auto-granted (created at runtime).
 *       Used by DirectoryPermissionService for access checks.
 */
class DirectoryRolePermissionRepository
{
    protected string $table = 'dam_directory_role';

    protected string $settingsTable = 'dam_role_settings';

    /**
     * All granted directory ids for the given role (explicit + auto-granted).
     * Reads dam_directory_role directly. Used by the view composer to populate
     * the permission editor with the full grant set, including dirs the user
     * created at runtime via addDirectoryToRole().
     *
     * @return array<int>
     */
    public function getAllGrantedIds(int $roleId): array
    {
        return DB::table($this->table)
            ->where('role_id', $roleId)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Admin-selected (explicit) directory ids for the given role.
     * Returns the roots the admin chose in the editor — does NOT include
     * auto-granted directories added at runtime.  Used as the diff baseline
     * in syncForRole() and the strip filter in EventServiceProvider.
     *
     * @return array<int>
     */
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

    /**
     * Auto-grant a single directory to a role when the owning admin creates it.
     * Stored directly in dam_directory_role; NOT written to explicit_directory_ids
     * so it survives the next role save without being wiped.
     * No-op if the grant already exists.
     */
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

    /**
     * Sync admin-selected (explicit) grants for a role.
     *
     * Uses a diff strategy so auto-granted rows (directories the user created at
     * runtime) are preserved:
     *   • Adds rows for IDs that are newly explicit.
     *   • Removes rows for IDs that were previously explicit but are no longer —
     *     AND cascades the removal to any auto-granted descendants of those removed
     *     roots (so revoking a parent also revokes its children).
     *   • Leaves all other rows (auto-grants for kept/new subtrees) untouched.
     *   • Saves the new explicit set to dam_role_settings for the next diff.
     *
     * @param  array<int>  $directoryIds
     */
    public function syncForRole(int $roleId, array $directoryIds): void
    {
        $newSubmitted = array_values(array_unique(array_map('intval', $directoryIds)));
        $prevExplicit = $this->getDirectoryIdsForRole($roleId);

        $existingInPivot = DB::table($this->table)
            ->where('role_id', $roleId)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Auto-granted = in dam_directory_role but NOT in explicit_directory_ids.
        // When the JS submits all visually-checked dirs (explicit + auto-granted),
        // we must exclude pre-existing auto-grants from the new explicit set so
        // they remain auto-granted and are never accidentally wiped on a future save.
        $autoGranted = array_values(array_diff($existingInPivot, $prevExplicit));
        $newExplicit = array_values(array_diff($newSubmitted, $autoGranted));
        $toRemove = array_values(array_diff($prevExplicit, $newSubmitted));
        $toAdd = array_values(array_diff($newExplicit, $existingInPivot));

        DB::transaction(function () use ($roleId, $newExplicit, $toRemove, $toAdd) {
            // When an explicit root is revoked, also remove any auto-granted descendants
            // that live under it (revoking parent = revoking entire subtree).
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

            // Insert newly-explicit grants.
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

            // Persist the new explicit set so the next save has a correct baseline.
            DB::table($this->settingsTable)
                ->where('role_id', $roleId)
                ->update(['explicit_directory_ids' => json_encode($newExplicit)]);
        });
    }
}
