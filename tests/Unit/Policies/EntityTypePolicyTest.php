<?php

namespace Entities13\Tests\Unit\Policies;

use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Entities13\Policies\EntityTypePolicy;
use Basics13\Support\Testing\PermissionsActor;
use PHPUnit\Framework\Attributes\DataProvider;

class EntityTypePolicyTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function recordAbilities(): array
    {
        return [
            'update' => ['update', 'entity_types.edit'],
            'delete' => ['delete', 'entity_types.delete'],
            'restore' => ['restore', 'entity_types.restore'],
            'forceDelete' => ['forceDelete', 'entity_types.force_delete'],
            'archive' => ['archive', 'entity_types.archive'],
            'activate' => ['activate', 'entity_types.archive'],
        ];
    }

    public function test_listing_and_creating_need_their_permission(): void
    {
        $policy = new EntityTypePolicy;

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['entity_types.view'])));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding([])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['entity_types.create'])));
        $this->assertFalse($policy->create(PermissionsActor::holding(['entity_types.view'])));
    }

    #[DataProvider('recordAbilities')]
    public function test_each_ability_on_a_record_needs_its_permission(string $ability, string $permission): void
    {
        $policy = new EntityTypePolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding([$permission]), new EntityType));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['entity_types.view']), new EntityType));
    }

    #[DataProvider('recordAbilities')]
    public function test_only_the_manage_of_its_own_module_grants_every_ability(string $ability, string $permission): void
    {
        $policy = new EntityTypePolicy;

        $this->assertTrue($policy->{$ability}(PermissionsActor::holding(['entity_types.manage']), new EntityType));
        $this->assertFalse($policy->{$ability}(PermissionsActor::holding(['users.manage', str_replace('entity_types.', 'roles.', $permission)]), new EntityType));
    }
}
