<?php

use Webkul\DAM\DataGrids\Asset\AssetDataGrid;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('file_name filter uses LIKE matching so partial names return results', function () {
    $grid = app(AssetDataGrid::class);
    $grid->setQueryBuilder();
    $grid->prepareColumns();

    $qb = $grid->processRequestedFilters(['file_name' => ['copybook']]);

    $sql = strtolower($qb->toSql());

    expect($sql)->toContain('like');
});

it('file_name filter SQL does not include extra exact-match clause from fall-through', function () {
    $grid = app(AssetDataGrid::class);
    $grid->setQueryBuilder();
    $grid->prepareColumns();

    $qb = $grid->processRequestedFilters(['file_name' => ['copybook']]);

    $bindings = $qb->getBindings();

    // Correct: only one binding per filter value ('%copybook%')
    // Buggy: three bindings per value ('%copybook%', 'copybook', 'copybook') due to fall-through
    $filterBindings = array_filter($bindings, fn ($b) => str_contains((string) $b, 'copybook'));

    expect(count($filterBindings))->toBe(1);
});
