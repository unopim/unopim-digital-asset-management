<?php

namespace Webkul\DAM\Tests;

use Tests\TestCase;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\User\Tests\Concerns\UserAssertions;

class DamTestCase extends TestCase
{
    use ApiHelperTrait, UserAssertions {
        UserAssertions::getFullTableName insteadof ApiHelperTrait;
    }
}
