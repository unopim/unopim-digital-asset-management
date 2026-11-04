<?php

declare(strict_types=1);

namespace Webkul\DAM\Integrations\Pim;

use Webkul\DAM\Contracts\Adapters\RoleResolver;
use Webkul\User\Models\Role;

class PimRoleResolver implements RoleResolver
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return Role::class;
    }

    public function find(int|string $id): ?object
    {
        return Role::find($id);
    }

    /**
     * @return iterable<object>
     */
    public function all(): iterable
    {
        return Role::all();
    }
}
