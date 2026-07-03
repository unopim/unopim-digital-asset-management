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
}
