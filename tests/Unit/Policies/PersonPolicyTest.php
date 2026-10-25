<?php

namespace Entities13\Tests\Unit\Policies;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Policies\PersonPolicy;
use Basics13\Support\Testing\PermissionsActor;
use PHPUnit\Framework\Attributes\DataProvider;

class PersonPolicyTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function recordAbilities(): array
    {
        return [
            'update' => ['update', 'persons.edit'],
            'delete' => ['delete', 'persons.delete'],
            'restore' => ['restore', 'persons.restore'],
            'forceDelete' => ['forceDelete', 'persons.force_delete'],
            'archive' => ['archive', 'persons.archive'],
            'activate' => ['activate', 'persons.archive'],
        ];
    }

    public function test_listing_and_creating_need_their_permission(): void
    {
        $policy = new PersonPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['persons.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding([])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['persons.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['persons.view'])));
    }

    #[DataProvider('recordAbilities')]
    public function test_each_ability_on_a_record_needs_its_permission(string $ability, string $permission): void
    {
        $policy = new PersonPolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding([$permission]), new Person));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['persons.view']), new Person));
    }

    #[DataProvider('recordAbilities')]
    public function test_only_the_manage_of_its_own_module_grants_every_ability(string $ability, string $permission): void
    {
        $policy = new PersonPolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding(['persons.manage']), new Person));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['users.manage', str_replace('persons.', 'roles.', $permission)]), new Person));
    }
}
