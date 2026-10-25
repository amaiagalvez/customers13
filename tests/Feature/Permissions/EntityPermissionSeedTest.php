<?php

namespace Entities13\Tests\Feature\Permissions;

use Basics13\Models\Role;
use Entities13\Tests\TestCase;
use Basics13\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\EntitiesAccessSeeder;

class EntityPermissionSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_full_access_to_entities_and_entity_types(): void
    {
        $this->assertSame(8, Permission::query()->where('module', 'entities')->count());
        $this->assertSame(8, Permission::query()->where('module', 'entity_types')->count());
        $this->assertEqualsCanonicalizing(
            ['entities.manage', 'entity_types.manage'],
            Role::query()->where('slug', 'admin')->firstOrFail()->permissions()
                ->whereIn('module', ['entities', 'entity_types'])->pluck('slug')->all(),
        );
    }

    public function test_running_it_again_duplicates_nothing(): void
    {
        $counts = [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()];

        $this->seed(EntitiesAccessSeeder::class);

        $this->assertSame($counts, [DB::table('BAS_permissions')->count(), DB::table('BAS_permission_role')->count()]);
    }
}
