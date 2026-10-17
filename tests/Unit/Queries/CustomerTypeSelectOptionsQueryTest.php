<?php

namespace Customers13\Tests\Unit\Queries;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Customers13\Models\CustomerType;
use Customers13\Contracts\CustomerIntegration;
use Customers13\Queries\CustomerTypes\CustomerTypeSelectOptionsQuery;

/**
 * The select-options query searches by itself and never counts a state tab, so the two hooks the
 * base declares never come up on its own path. They are still its contract: the base asks this
 * query for its model and for its integration, and both answers have to be the ones the query was
 * built with.
 */
class CustomerTypeSelectOptionsQueryTest extends TestCase
{
    public function test_the_query_answers_the_base_with_the_customer_type_model(): void
    {
        $method = new ReflectionMethod(CustomerTypeSelectOptionsQuery::class, 'getModelClass');
        $method->setAccessible(true);

        $this->assertSame(
            CustomerType::class,
            $method->invoke(new CustomerTypeSelectOptionsQuery($this->nullIntegration())),
        );
    }

    public function test_the_query_answers_the_base_with_the_integration_it_was_given(): void
    {
        $integration = $this->nullIntegration();
        $method = new ReflectionMethod(CustomerTypeSelectOptionsQuery::class, 'integration');
        $method->setAccessible(true);

        $this->assertSame($integration, $method->invoke(new CustomerTypeSelectOptionsQuery($integration)));
    }

    private function nullIntegration(): CustomerIntegration
    {
        return new class extends CustomerIntegration {};
    }
}
