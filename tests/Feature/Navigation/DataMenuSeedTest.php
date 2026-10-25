<?php

namespace Entities13\Tests\Feature\Navigation;

use Basics13\Models\User;
use Entities13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Basics13\Support\NavigationMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\EntitiesAccessSeeder;

class DataMenuSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_it_again_duplicates_nothing(): void
    {
        $counts = [DB::table('BAS_menus')->count(), DB::table('BAS_menu_permission')->count()];

        $this->seed(EntitiesAccessSeeder::class);

        $this->assertSame($counts, [DB::table('BAS_menus')->count(), DB::table('BAS_menu_permission')->count()]);
    }

    public function test_each_link_asks_for_its_list_permission(): void
    {
        $group = collect(NavigationMenu::for(User::factory()->withPermissions(['entity_types.view'])->create()))
            ->firstWhere('item.key', 'entities13');

        $this->assertSame(['entities-auxiliary'], array_column(array_column($group['children'] ?? [], 'item'), 'key'));
        $this->assertSame(['entity-types'], array_column(array_column($group['children'][0]['children'] ?? [], 'item'), 'key'));
        $this->assertNull(collect(NavigationMenu::for(User::factory()->create()))->firstWhere('item.key', 'entities13'));
    }

    public function test_the_auxiliary_tables_hang_from_their_own_subgroup(): void
    {
        $this->assertSame(
            ['entities' => 'entities13', 'entities-auxiliary' => 'entities13', 'entity-types' => 'entities-auxiliary', 'person-types' => 'entities-auxiliary', 'persons' => 'entities13'],
            DB::table('BAS_menus')->where('package', 'entities13')->whereNotNull('parent_key')->orderBy('key')->pluck('parent_key', 'key')->all(),
        );
    }

    public function test_the_old_data_group_is_gone(): void
    {
        DB::table('BAS_menus')->insert(['key' => 'data', 'package' => 'entities13', 'label' => 'Data', 'sort' => 200, 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(EntitiesAccessSeeder::class);

        $this->assertFalse(DB::table('BAS_menus')->where('key', 'data')->exists());
    }
}
