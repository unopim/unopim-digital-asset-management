<?php

declare(strict_types=1);

namespace Webkul\DAM\Integrations\Standalone;

use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Contracts\Adapters\RoleResolver;
use Webkul\DAM\Models\Role;

class DamRoleResolver implements RoleResolver
{
    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function modelClass(): string
    {
        return Role::class;
    }

    public function find(int|string $id): ?object
    {
        if (! $this->hasTable()) {
            return null;
        }

        return Role::find($id);
    }

    /**
     * @return iterable<object>
     */
    public function all(): iterable
    {
        if (! $this->hasTable()) {
            return [];
        }

        return Role::all();
    }

    protected function hasTable(): bool
    {
        try {
            return Schema::hasTable('roles');
        } catch (\Throwable) {
            return false;
        }
    }
}
