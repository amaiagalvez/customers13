<?php

namespace entities13\Tests\Unit\Queries;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use entities13\Models\Entity;
use entities13\Contracts\EntityIntegration;
use entities13\Queries\Entities\EntitySelectOptionsQuery;

/**
 * The select-options query searches by itself and never counts a state tab, so the two hooks the
 * base declares never come up on its own path. They are still its contract: the base asks this
 * query for its model and for its integration, and both answers have to be the ones the query was
 * built with.
 */
class EntitySelectOptionsQueryTest extends TestCase
{
    public function test_the_query_answers_the_base_with_the_entity_model(): void
    {
        $method = new ReflectionMethod(EntitySelectOptionsQuery::class, 'getModelClass');
        $method->setAccessible(true);

        $this->assertSame(
            Entity::class,
            $method->invoke(new EntitySelectOptionsQuery($this->nullIntegration())),
        );
    }

    public function test_the_query_answers_the_base_with_the_integration_it_was_given(): void
    {
        $integration = $this->nullIntegration();
        $method = new ReflectionMethod(EntitySelectOptionsQuery::class, 'integration');
        $method->setAccessible(true);

        $this->assertSame($integration, $method->invoke(new EntitySelectOptionsQuery($integration)));
    }

    private function nullIntegration(): EntityIntegration
    {
        return new class extends EntityIntegration {};
    }
}
