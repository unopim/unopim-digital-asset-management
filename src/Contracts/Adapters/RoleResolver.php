<?php

declare(strict_types=1);

namespace Webkul\DAM\Contracts\Adapters;

interface RoleResolver extends PimIntegration
{
    public function modelClass(): string;

    public function find(int|string $id): ?object;

    /**
     * @return iterable<object>
     */
    public function all(): iterable;
}
