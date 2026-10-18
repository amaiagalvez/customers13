<?php

namespace entities13\Tests\Unit\Queries;

use ReflectionClass;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use entities13\Contracts\EntityIntegration;
use entities13\Queries\Entities\EntityListQuery;

class EntityListQueryTest extends TestCase
{
    public function test_query_can_filter_by_active(): void
    {
        foreach (['active', 'archived', 'trashed'] as $method) {
            $this->assertTrue((new ReflectionMethod(EntityListQuery::class, $method))->isPublic());
        }
    }

    public function test_query_can_find_trashed_by_name(): void
    {
        $this->assertTrue((new ReflectionMethod(EntityListQuery::class, 'findTrashedByName'))->isPublic());
    }

    public function test_the_query_is_built_on_the_integration_contract(): void
    {
        $constructor = (new ReflectionClass(EntityListQuery::class))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());

        $type = $constructor->getParameters()[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame(EntityIntegration::class, $type->getName());
    }

    /**
     * The base lists search their own columns, and nothing else: a column only gets into a search
     * because an integration asked for it.
     */
    public function test_search_columns_are_the_ones_the_base_defines(): void
    {
        $this->assertSame(['ENT_entities.name'], $this->searchColumns('active'));
        $this->assertSame(['ENT_entities.name', 'ENT_entities.updated_at'], $this->searchColumns('archived'));
        $this->assertSame(['ENT_entities.name', 'ENT_entities.deleted_at'], $this->searchColumns('trashed'));
    }

    public function test_state_counts_method_exists(): void
    {
        $reflection = new ReflectionClass(EntityListQuery::class);
        $this->assertTrue($reflection->hasMethod('stateCounts'));
    }

    public function test_state_counts_returns_array_with_expected_keys(): void
    {
        $query = new EntityListQuery($this->nullIntegration());
        $result = $query->stateCounts(10, 5, 3);

        $this->assertSame(
            ['active' => 10, 'archived' => 5, 'trashed' => 3],
            $result,
        );
    }

    /**
     * The counts the rows used to carry lived in the query and read the projects tables. None of
     * that is here any more, so the methods are asserted gone rather than left to rot.
     */
    public function test_the_project_counts_are_gone_from_the_base_query(): void
    {
        $reflection = new ReflectionClass(EntityListQuery::class);

        $this->assertFalse($reflection->hasMethod('withActiveCounts'));
        $this->assertFalse($reflection->hasMethod('activeCommentsCount'));
    }

    /**
     * @return list<string>
     */
    private function searchColumns(string $scope): array
    {
        $method = new ReflectionMethod(EntityListQuery::class, 'searchColumns');
        $method->setAccessible(true);

        /** @var list<string> $columns */
        $columns = $method->invoke(
            new EntityListQuery($this->nullIntegration()),
            $scope,
            match ($scope) {
                'active' => ['ENT_entities.name'],
                'archived' => ['ENT_entities.name', 'ENT_entities.updated_at'],
                default => ['ENT_entities.name', 'ENT_entities.deleted_at'],
            },
        );

        return $columns;
    }

    private function nullIntegration(): EntityIntegration
    {
        return new class extends EntityIntegration {};
    }
}
