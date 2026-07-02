<?php

namespace Webkul\DAM\Database\Eloquent;

use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\QueryBuilder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Handle the operation.
     *
     * @return Collection
     */
    public function ancestorsAndSelfAndDefaultOrder(int $id, array $columns = ['*'])
    {
        return $this->whereAncestorOf($id, true)->defaultOrder()->get($columns);
    }
}
