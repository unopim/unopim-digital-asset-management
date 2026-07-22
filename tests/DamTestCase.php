<?php

namespace Webkul\DAM\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\User\Tests\Concerns\UserAssertions;

class DamTestCase extends TestCase
{
    use ApiHelperTrait, UserAssertions {
        UserAssertions::getFullTableName insteadof ApiHelperTrait;
    }

    /**
     * Give every DAM test a clean runtime-config baseline.
     *
     * The `DAM` middleware re-applies rows from `dam_configuration` onto the
     * config repository on every request, which would otherwise override a
     * test's own `config()->set(...)` (e.g. `dam.tree.show_assets`). Any rows a
     * developer toggled through the UI on a shared dev DB would then silently
     * break config-dependent tests. Clearing the table here runs inside the
     * DatabaseTransactions wrapper, so it is rolled back and the real
     * configuration is left untouched.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('dam_configuration')) {
            DB::table('dam_configuration')->delete();
        }
    }

    /**
     * Clear every asset-owning DAM table so a test can assert absolute row counts.
     *
     * Tests that verify a seeder produced exactly N rows are otherwise skewed by
     * whatever a developer already created on a shared dev database. Like the
     * `dam_configuration` reset above this runs inside the DatabaseTransactions
     * wrapper, so it is rolled back and real data is left untouched. Foreign key
     * checks are suspended because `dam_directories.parent_id` is self-referencing.
     */
    protected function damResetAssetTables(): void
    {
        $tables = [
            'dam_asset_comments',
            'dam_asset_directory',
            'dam_asset_properties',
            'dam_asset_resource_mappings',
            'dam_asset_tag',
            'dam_assets',
            'dam_tags',
            'dam_directory_role',
            'dam_explorer_bookmarks',
            'dam_directories',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
