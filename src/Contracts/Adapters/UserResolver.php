<?php

declare(strict_types=1);

namespace Webkul\DAM\Contracts\Adapters;

interface UserResolver extends PimIntegration
{
    public function modelClass(): string;

    public function current(): ?object;

    public function find(int|string $id): ?object;

    public function findByEmail(string $email): ?object;
}
