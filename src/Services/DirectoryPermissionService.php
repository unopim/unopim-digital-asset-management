<?php

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Directory;

class DirectoryPermissionService
{
    /**
     * Cache of viewable directory ids resolved for the current admin within a request.
     */
    protected ?array $viewableIdsCache = null;

    /**
     * The admin id that produced the cached viewable ids. Used to invalidate the cache
     * if the resolver is re-used across guard switches in long-lived processes.
     */
    protected ?int $cachedForAdminId = null;

    /**
     * True when directory ACL filtering should be skipped for the current request.
     *
     * Skipped for: anonymous requests and admins whose role has `permission_type != 'custom'`.
     * Enforced for: any authenticated admin (web or API) whose role is `permission_type = 'custom'`.
     */
    public function bypass(): bool
    {
        $admin = $this->currentAdmin();

        if (! $admin) {
            return true;
        }

        return optional($admin->role)->permission_type !== 'custom';
    }

    /**
     * Resolve all directory ids the current admin is allowed to view.
     * Memoised per request. Returns every directory id when the request bypasses
     * the filter (anonymous requests or admins with `permission_type = 'all'`).
     *
     * Granting a deep directory (e.g. Root/Audio and Video/Audio) implicitly
     * exposes its ancestors so the tree can render the path down to it.
     * Ancestors are visibility-only; write actions are still gated by the
     * explicit pivot grants via `directlyGrantedIds()`.
     */
    public function viewableIds(): array
    {
        if ($this->bypass()) {
            return Directory::query()->pluck('id')->all();
        }

        $admin = $this->currentAdmin();

        if ($this->viewableIdsCache !== null && $this->cachedForAdminId === $admin->id) {
            return $this->viewableIdsCache;
        }

        $granted = $this->directlyGrantedIds();

        if (empty($granted)) {
            $this->viewableIdsCache = [];
            $this->cachedForAdminId = $admin->id;

            return [];
        }

        // Self + ancestors, computed in a single nested-set query.
        $ids = DB::table('dam_directories as ancestor')
            ->join('dam_directories as descendant', function ($join) {
                $join->whereColumn('ancestor._lft', '<=', 'descendant._lft')
                    ->whereColumn('ancestor._rgt', '>=', 'descendant._rgt');
            })
            ->whereIn('descendant.id', $granted)
            ->distinct()
            ->pluck('ancestor.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->viewableIdsCache = $ids;
        $this->cachedForAdminId = $admin->id;

        return $ids;
    }

    /**
     * Directly granted directory ids for the current admin's role — the raw pivot
     * rows, no ancestor expansion. Used for write-action gating where ancestors
     * should NOT count.
     *
     * @return array<int>
     */
    public function directlyGrantedIds(): array
    {
        $admin = $this->currentAdmin();

        if (! $admin) {
            return [];
        }

        return DB::table('dam_directory_role')
            ->where('role_id', $admin->role_id)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Whether the directory is visible in the tree for the current admin.
     * Ancestors of a granted directory count as visible so the tree can render
     * the path down to the grant. Use this for tree navigation only.
     */
    public function canView(int $directoryId): bool
    {
        if ($this->bypass()) {
            return true;
        }

        return in_array($directoryId, $this->viewableIds(), true);
    }

    /**
     * Whether the current admin can act on a directory's contents — list assets,
     * create children, rename, move, delete, upload. Stricter than canView():
     * only directly-granted directories pass; ancestors that became "visible"
     * through expansion do NOT.
     */
    public function canAccess(int $directoryId): bool
    {
        if ($this->bypass()) {
            return true;
        }

        return in_array($directoryId, $this->directlyGrantedIds(), true);
    }

    /**
     * Reset the request-local cache. Useful in tests that switch the auth user.
     */
    public function flush(): void
    {
        $this->viewableIdsCache = null;
        $this->cachedForAdminId = null;
    }

    /**
     * Resolve the authenticated Admin for the current request.
     * Checks the web `admin` guard first, then the Passport `api` guard,
     * so both web sessions and API tokens resolve to the same Admin model
     * and go through identical directory permission filtering.
     */
    protected function currentAdmin()
    {
        try {
            return auth()->guard('admin')->user()
                ?? auth()->guard('api')->user();
        } catch (\BadMethodCallException) {
            return null;
        }
    }
}
