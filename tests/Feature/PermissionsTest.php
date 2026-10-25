<?php

namespace Entities13\Tests\Feature;

use Basics13\Models\Role;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\EntitiesAccessSeeder;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_view_persons(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $response = $this->get(route('persons.index'));

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_view_person_types(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $response = $this->get(route('person-types.index'));

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_create_person(): void
    {
        $this->actingAs($this->createUserWithPermissions(['persons.view']));

        $response = $this->post(route('persons.store'), [
            'name' => 'Test',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_person(): void
    {
        $person = Person::factory()->create();
        $this->actingAs($this->createUserWithPermissions(['persons.view']));

        $response = $this->delete(route('persons.destroy', $person));

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_create_person_type(): void
    {
        $this->actingAs($this->createUserWithPermissions(['person_types.view']));

        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Test',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_person_type(): void
    {
        $type = PersonType::factory()->create();
        $this->actingAs($this->createUserWithPermissions(['person_types.view']));

        $response = $this->delete(route('person-types.destroy', $type));

        $response->assertForbidden();
    }

    public function test_seeder_is_idempotent(): void
    {
        $counts = [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()];

        $this->seed(EntitiesAccessSeeder::class);

        $this->assertSame($counts, [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()]);
    }

    public function test_admin_role_has_persons_permissions(): void
    {
        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $permissions = $adminRole->permissions->pluck('slug')->toArray();

        $this->assertContains('persons.manage', $permissions);
        $this->assertContains('person_types.manage', $permissions);
        // The admin role gets .manage which implies all other permissions via HasRoles::hasPermissionTo
        $this->assertNotContains('persons.view', $permissions); // Not granted explicitly, but implied by .manage
        $this->assertNotContains('person_types.view', $permissions);
    }
}
