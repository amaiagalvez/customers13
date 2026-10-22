<?php

namespace Entities13\Tests\Feature\Permissions;

use App\Models\User;
use Basics13\Models\Role;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_entities_needs_entities_view(): void
    {
        $this->actingAs($this->holding(['entity_types.manage']));

        $this->get(route('entities.index'))->assertForbidden();
        $this->get(route('entities.archived.index'))->assertForbidden();
        $this->get(route('entities.trash.index'))->assertForbidden();
        $this->getJson(route('entities.options'))->assertForbidden();
    }

    public function test_a_read_only_user_cannot_edit_an_entity(): void
    {
        $entity = Entity::factory()->create(['name' => 'Original name']);
        $this->actingAs($this->holding(['entities.view']));

        $this->get(route('entities.index'))->assertOk();
        $this->put(route('entities.update', $entity), ['name' => 'Changed name'])->assertForbidden();

        $this->assertSame('Original name', $entity->refresh()->name);
    }

    public function test_emptying_the_trash_needs_force_delete(): void
    {
        $entity = Entity::factory()->trashed()->create();
        $this->actingAs($this->holding(['entities.view', 'entities.delete', 'entities.restore']));

        $this->delete(route('entities.trash.destroy', $entity->id))->assertForbidden();

        $this->assertSoftDeleted($entity);
    }

    public function test_entity_types_need_their_own_permissions(): void
    {
        $type = EntityType::factory()->create();
        $this->actingAs($this->holding(['entities.manage']));

        $this->get(route('entity-types.index'))->assertForbidden();
        $this->delete(route('entity-types.destroy', $type))->assertForbidden();

        $this->assertNotSoftDeleted($type);
    }

    public function test_admin_keeps_managing_entities_and_types(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Admin user',
            'email' => 'admin@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        $this->get(route('entities.index'))->assertOk();
        $this->get(route('entity-types.index'))->assertOk();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function holding(array $permissions): User
    {
        $user = User::create([
            'name' => 'Limited user',
            'email' => 'limited@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::factory()->withPermissions($permissions)->create());

        return $user;
    }
}
