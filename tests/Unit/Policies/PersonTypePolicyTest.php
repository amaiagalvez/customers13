<?php

namespace Entities13\Tests\Unit\Policies;

use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Entities13\Policies\PersonTypePolicy;
use Basics13\Support\Testing\PermissionsActor;
use PHPUnit\Framework\Attributes\DataProvider;

class PersonTypePolicyTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function recordAbilities(): array
    {
        return [
            'update' => ['update', 'person_types.edit'],
            'delete' => ['delete', 'person_types.delete'],
            'restore' => ['restore', 'person_types.restore'],
            'forceDelete' => ['forceDelete', 'person_types.force_delete'],
            'archive' => ['archive', 'person_types.archive'],
            'activate' => ['activate', 'person_types.archive'],
        ];
    }

    public function test_listing_and_creating_need_their_permission(): void
    {
        $policy = new PersonTypePolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['person_types.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding([])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['person_types.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['person_types.view'])));
    }

    #[DataProvider('recordAbilities')]
    public function test_each_ability_on_a_record_needs_its_permission(string $ability, string $permission): void
    {
        $policy = new PersonTypePolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding([$permission]), new PersonType));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['person_types.view']), new PersonType));
    }

    #[DataProvider('recordAbilities')]
    public function test_only_the_manage_of_its_own_module_grants_every_ability(string $ability, string $permission): void
    {
        $policy = new PersonTypePolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding(['person_types.manage']), new PersonType));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['users.manage', str_replace('person_types.', 'roles.', $permission)]), new PersonType));
    }
}
