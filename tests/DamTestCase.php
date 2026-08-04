<?php

namespace Webkul\DAM\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\User\Tests\Concerns\UserAssertions;

class DamTestCase extends TestCase
{
    use ApiHelperTrait, UserAssertions {
        UserAssertions::getFullTableName insteadof ApiHelperTrait;
    }

    protected const TEST_ROOT_URL = 'http://localhost';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::TEST_ROOT_URL]);

        URL::forceRootUrl(self::TEST_ROOT_URL);

        if (Schema::hasTable('dam_configuration')) {
            DB::table('dam_configuration')->delete();
        }
    }

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
