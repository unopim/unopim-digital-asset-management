<?php

declare(strict_types=1);

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Schema;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Role;

class PimIntegration
{
    protected static ?bool $override = null;

    protected static ?bool $schemaCache = null;

    public static function fake(?bool $enabled): void
    {
        self::$override = $enabled;
    }

    public static function clearFake(): void
    {
        self::$override = null;
        self::$schemaCache = null;
    }

    public static function corePresent(): bool
    {
        return class_exists(Product::class);
    }

    public static function rolePresent(): bool
    {
        return class_exists(Role::class);
    }

    public static function catalogSchemaPresent(): bool
    {
        if (self::$schemaCache !== null) {
            return self::$schemaCache;
        }

        try {
            return self::$schemaCache = Schema::hasTable('categories');
        } catch (\Throwable) {
            return self::$schemaCache = false;
        }
    }

    public static function enabled(): bool
    {
        if (self::$override !== null) {
            return self::$override;
        }

        return self::corePresent() && self::catalogSchemaPresent();
    }
}
