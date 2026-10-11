<?php

namespace Customers13\Tests\Unit\Queries;

use ReflectionClass;
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

    public function test_search_columns_constant_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasConstant('SEARCH_COLUMNS'));
        $columns = $reflection->getConstant('SEARCH_COLUMNS');
        $this->assertIsArray($columns);
        $this->assertContains('name', $columns);
    }

    public function test_search_columns_archived_constant_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasConstant('SEARCH_COLUMNS_ARCHIVED'));
        $columns = $reflection->getConstant('SEARCH_COLUMNS_ARCHIVED');
        $this->assertIsArray($columns);
        $this->assertContains('name', $columns);
        $this->assertContains('updated_at', $columns);
    }

    public function test_search_columns_trashed_constant_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasConstant('SEARCH_COLUMNS_TRASHED'));
        $columns = $reflection->getConstant('SEARCH_COLUMNS_TRASHED');
        $this->assertIsArray($columns);
        $this->assertContains('name', $columns);
        $this->assertContains('deleted_at', $columns);
    }

    public function test_state_counts_method_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasMethod('stateCounts'));
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new CustomerListQuery;
        $result = $query->stateCounts(10, 5, 3);

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $result,
        );
    }

    public function test_with_active_counts_method_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasMethod('withActiveCounts'));
        $this->assertTrue($reflection->getMethod('withActiveCounts')->isPrivate());
    }

    public function test_active_comments_count_method_exists(): void
    {
        $reflection = new ReflectionClass(CustomerListQuery::class);
        $this->assertTrue($reflection->hasMethod('activeCommentsCount'));
        $this->assertTrue($reflection->getMethod('activeCommentsCount')->isPrivate());
    }
}
