<?php

namespace Customers13\Tests\Unit\Queries;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Customers13\Queries\Customers\CustomerListQuery;

class CustomerListQueryTest extends TestCase
{
    public function test_query_can_filter_by_active(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new ReflectionMethod(CustomerListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new ReflectionMethod(CustomerListQuery::class, 'findTrashedByName'))->isPublic());
    }
}
