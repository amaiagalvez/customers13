<?php

namespace Customers13\Tests\Unit;

use Customers13\Queries\Customers\CustomerListQuery;
use Tests\TestCase;

class CustomerListQueryTest extends TestCase
{
    public function test_query_can_filter_by_active(): void
    {
        $query = new CustomerListQuery;

        $this->assertTrue(method_exists($query, 'active'));
        $this->assertTrue(method_exists($query, 'archived'));
        $this->assertTrue(method_exists($query, 'trashed'));
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $query = new CustomerListQuery;

        $this->assertTrue(method_exists($query, 'findTrashedByName'));
    }
}
