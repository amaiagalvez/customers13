<?php

namespace entities13\Tests\Unit\Queries;

use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use entities13\Contracts\EntityIntegration;
use entities13\Queries\EntityTypes\EntityTypeListQuery;

class EntityTypeListQueryTest extends TestCase
{
    public function test_query_can_filter_by_state(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new ReflectionMethod(EntityTypeListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new ReflectionMethod(EntityTypeListQuery::class, 'findTrashedByName'))->isPublic());
    }

    public function test_the_query_is_built_on_the_integration_contract(): void
    {
        $constructor = (new ReflectionClass(EntityTypeListQuery::class))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());

        $type = $constructor->getParameters()[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame(EntityIntegration::class, $type->getName());
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new EntityTypeListQuery($this->nullIntegration());

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $query->stateCounts(10, 5, 3),
        );
    }

    /**
     * A type is searched by its four names, and the trashed list also matches the delete date while
     * the archived one matches the update date: the dates are the extra column those lists show.
     */
    public function test_the_search_columns_are_the_four_names(): void
    {
        $names = (new ReflectionClass(EntityTypeListQuery::class))->getConstant('NAME_COLUMNS');

        $this->assertSame([
            'CUM_entity_types.name_eu',
            'CUM_entity_types.name_es',
            'CUM_entity_types.name_fr',
            'CUM_entity_types.name_en',
        ], $names);
    }

    /**
     * A trashed type is looked up by its euskera name, which is the only name a type is required to
     * have and the one the create form asks for first.
     */
    public function test_the_trashed_lookup_is_overridden_to_use_the_euskera_name(): void
    {
        $method = new ReflectionMethod(EntityTypeListQuery::class, 'firstTrashedByName');

        $this->assertTrue($method->isProtected());
        $this->assertSame(EntityTypeListQuery::class, $method->getDeclaringClass()->getName());
    }

    private function nullIntegration(): EntityIntegration
    {
        return new class extends EntityIntegration {};
    }

    public function test_the_integration_method_is_protected(): void
    {
        $method = new ReflectionMethod(EntityTypeListQuery::class, 'integration');

        $this->assertTrue($method->isProtected());
        $this->assertSame(EntityTypeListQuery::class, $method->getDeclaringClass()->getName());

        $integration = $this->nullIntegration();

        $this->assertSame($integration, $method->invoke(new EntityTypeListQuery($integration)));
    }
}
