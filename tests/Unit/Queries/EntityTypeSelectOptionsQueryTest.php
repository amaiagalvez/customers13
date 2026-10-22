<?php

namespace Entities13\Tests\Unit\Queries;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Entities13\Models\EntityType;
use Entities13\Contracts\EntityIntegration;
use Entities13\Queries\EntityTypes\EntityTypeSelectOptionsQuery;

/**
 * The select-options query searches by itself and never counts a state tab, so the two hooks the
 * base declares never come up on its own path. They are still its contract: the base asks this
 * query for its model and for its integration, and both answers have to be the ones the query was
 * built with.
 */
class EntityTypeSelectOptionsQueryTest extends TestCase
{
    public function test_the_query_answers_the_base_with_the_entity_type_model(): void
    {
        $method = new ReflectionMethod(EntityTypeSelectOptionsQuery::class, 'getModelClass');

        $this->assertSame(
            EntityType::class,
            $method->invoke(new EntityTypeSelectOptionsQuery($this->nullIntegration())),
        );
    }

    public function test_the_query_answers_the_base_with_the_integration_it_was_given(): void
    {
        $integration = $this->nullIntegration();
        $method = new ReflectionMethod(EntityTypeSelectOptionsQuery::class, 'integration');

        $this->assertSame($integration, $method->invoke(new EntityTypeSelectOptionsQuery($integration)));
    }

    private function nullIntegration(): EntityIntegration
    {
        return new class extends EntityIntegration {};
    }
}
