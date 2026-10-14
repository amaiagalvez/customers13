<?php

namespace Customers13\Tests\Unit\Queries;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Customers13\Models\Customer;
use Customers13\Contracts\NullCustomerIntegration;
use Customers13\Queries\Customers\CustomerSelectOptionsQuery;

/**
 * The select-options query searches by itself and never counts a state tab, so the two hooks the
 * base declares never come up on its own path. They are still its contract: the base asks this
 * query for its model and for its integration, and both answers have to be the ones the query was
 * built with.
 */
class CustomerSelectOptionsQueryTest extends TestCase
{
    public function test_the_query_answers_the_base_with_the_customer_model(): void
    {
        $method = new ReflectionMethod(CustomerSelectOptionsQuery::class, 'getModelClass');
        $method->setAccessible(true);

        $this->assertSame(
            Customer::class,
            $method->invoke(new CustomerSelectOptionsQuery(new NullCustomerIntegration)),
        );
    }

    public function test_the_query_answers_the_base_with_the_integration_it_was_given(): void
    {
        $integration = new NullCustomerIntegration;
        $method = new ReflectionMethod(CustomerSelectOptionsQuery::class, 'integration');
        $method->setAccessible(true);

        $this->assertSame($integration, $method->invoke(new CustomerSelectOptionsQuery($integration)));
    }
}
