<?php

namespace Entities13\Tests\Unit\Queries;

use ReflectionMethod;
use Entities13\Models\Person;
use PHPUnit\Framework\TestCase;
use Entities13\Models\PersonType;
use Basics13\Queries\ListQueryBase;
use Entities13\Contracts\EntityIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Entities13\Queries\Persons\PersonListQuery;
use Entities13\Queries\PersonTypes\PersonTypeListQuery;
use Entities13\Queries\Persons\PersonSelectOptionsQuery;
use Entities13\Queries\PersonTypes\PersonTypeSelectOptionsQuery;

/**
 * The base asks each person query for its model and its integration; both answers have to be the
 * ones the query was built with, even where the query's own path never asks.
 */
class PersonQueryHooksTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function queries(): array
    {
        return [
            'person list' => [PersonListQuery::class, Person::class],
            'person options' => [PersonSelectOptionsQuery::class, Person::class],
            'person type list' => [PersonTypeListQuery::class, PersonType::class],
            'person type options' => [PersonTypeSelectOptionsQuery::class, PersonType::class],
        ];
    }

    /**
     * @param  class-string  $queryClass
     * @param  class-string  $modelClass
     */
    #[DataProvider('queries')]
    public function test_the_query_answers_the_base_with_its_model_and_integration(string $queryClass, string $modelClass): void
    {
        $integration = new class extends EntityIntegration {};
        $query = new $queryClass($integration);
        $this->assertInstanceOf(ListQueryBase::class, $query);

        $this->assertSame($modelClass, (new ReflectionMethod($query, 'getModelClass'))->invoke($query));
        $this->assertSame($integration, (new ReflectionMethod($query, 'integration'))->invoke($query));
    }

    public function test_the_lists_without_filters_offer_none(): void
    {
        $integration = new class extends EntityIntegration {};

        $this->assertSame([], (new PersonTypeListQuery($integration))->searchableFields('active'));
        $this->assertSame([], (new PersonTypeSelectOptionsQuery($integration))->searchableFields('active'));
        $this->assertSame([], (new PersonSelectOptionsQuery($integration))->searchableFields('active'));
    }
}
