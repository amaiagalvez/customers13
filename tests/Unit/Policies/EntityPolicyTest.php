<?php

namespace Entities13\Tests\Unit\Policies;

use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Policies\EntityPolicy;
use Basics13\Support\Testing\PermissionsActor;
use PHPUnit\Framework\Attributes\DataProvider;

class EntityPolicyTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function recordAbilities(): array
    {
        return [
            'update' => ['update', 'entities.edit'],
            'delete' => ['delete', 'entities.delete'],
            'restore' => ['restore', 'entities.restore'],
            'forceDelete' => ['forceDelete', 'entities.force_delete'],
            'archive' => ['archive', 'entities.archive'],
            'activate' => ['activate', 'entities.archive'],
        ];
    }

    public function test_listing_and_creating_need_their_permission(): void
    {
        $policy = new EntityPolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['entities.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding([])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['entities.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['entities.view'])));
    }

    #[DataProvider('recordAbilities')]
    public function test_each_ability_on_a_record_needs_its_permission(string $ability, string $permission): void
    {
        $policy = new EntityPolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding([$permission]), new Entity));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['entities.view']), new Entity));
    }

    #[DataProvider('recordAbilities')]
    public function test_only_the_manage_of_its_own_module_grants_every_ability(string $ability, string $permission): void
    {
        $policy = new EntityPolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding(['entities.manage']), new Entity));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['users.manage', str_replace('entities.', 'roles.', $permission)]), new Entity));
    }
}
