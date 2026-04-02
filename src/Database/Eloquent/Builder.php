<?php

namespace Webkul\DAM\Database\Eloquent;

use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\QueryBuilder as BaseBuilder;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
class Builder extends BaseBuilder
{
    /**
     * @return Collection
     */
    public function ancestorsAndSelfAndDefaultOrder(int $id, array $columns = ['*'])
    {
        return $this->whereAncestorOf($id, true)->defaultOrder()->get($columns);
    }
}
