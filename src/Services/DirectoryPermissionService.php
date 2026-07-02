<?php

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Directory;

class DirectoryPermissionService
{
    /** Cache of viewable directory ids resolved for the current admin within a request. */
    protected ?array $viewableIdsCache = null;

    /** The admin id that produced the cached viewable ids. */
    protected ?int $cachedForAdminId = null;

    /** Cached bypass decision for the current request. */
    protected ?bool $bypassCache = null;

    /** Cached directly-granted ids (possibly with descendants). */
    protected ?array $directlyGrantedCache = null;

    /** The admin id that produced the cached directly-granted ids. */
    protected ?int $directlyGrantedForAdminId = null;

    /**
     * True when directory ACL filtering should be skipped for the current request.
     */
    public function bypass(): bool
    {
        if ($this->bypassCache !== null) {
            return $this->bypassCache;
        }

        $admin = $this->currentAdmin();

        if (! $admin) {
            return $this->bypassCache = true;
        }

        if (optional($admin->role)->permission_type !== 'custom') {
            return $this->bypassCache = true;
        }

        return $this->bypassCache = DB::table('dam_role_settings')
            ->where('role_id', $admin->role_id)
            ->where('all_directories', true)
            ->exists();
    }

    /**
     * Resolve all directory ids the current admin is allowed to view (memoised per request).
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
     * Directly granted directory ids for the current admin's role (memoised per request).
     */
    public function directlyGrantedIds(): array
    {
        $admin = $this->currentAdmin();

        if (! $admin) {
            return [];
        }

        if ($this->directlyGrantedCache !== null && $this->directlyGrantedForAdminId === $admin->id) {
            return $this->directlyGrantedCache;
        }

        $explicit = DB::table('dam_directory_role')
            ->where('role_id', $admin->role_id)
            ->pluck('directory_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $inheritChildren = (bool) DB::table('dam_role_settings')
            ->where('role_id', $admin->role_id)
            ->value('inherit_children');

        if ($inheritChildren && ! empty($explicit)) {
            $descendants = DB::table('dam_directories as ancestor')
                ->join('dam_directories as descendant', function ($join) {
                    $join->whereColumn('descendant._lft', '>=', 'ancestor._lft')
                        ->whereColumn('descendant._rgt', '<=', 'ancestor._rgt');
                })
                ->whereIn('ancestor.id', $explicit)
                ->distinct()
                ->pluck('descendant.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ids = array_values(array_unique(array_merge($explicit, $descendants)));
        } else {
            $ids = $explicit;
        }

        $this->directlyGrantedCache = $ids;
        $this->directlyGrantedForAdminId = $admin->id;

        return $ids;
    }

    /**
     * Whether the directory is visible in the tree for the current admin.
     */
    public function canView(int $directoryId): bool
    {
        if ($this->bypass()) {
            return true;
        }

        return in_array($directoryId, $this->viewableIds(), true);
    }

    /**
     * Whether the current admin can act on a directory's contents (stricter than canView).
     */
    public function canAccess(int $directoryId): bool
    {
        if ($this->bypass()) {
            return true;
        }

        return in_array($directoryId, $this->directlyGrantedIds(), true);
    }

    /**
     * Reset the request-local cache.
     */
    public function flush(): void
    {
        $this->viewableIdsCache = null;
        $this->cachedForAdminId = null;
        $this->bypassCache = null;
        $this->directlyGrantedCache = null;
        $this->directlyGrantedForAdminId = null;
    }

    /** Resolve the authenticated Admin for the current request (admin guard, then api guard). */
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
