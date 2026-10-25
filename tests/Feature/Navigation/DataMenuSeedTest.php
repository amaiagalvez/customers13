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
        $data = collect(NavigationMenu::for(User::factory()->withPermissions(['entity_types.view'])->create()))
            ->firstWhere('item.key', 'data');

        $this->assertSame(['entity-types'], array_column($data['children'] ?? [], 'key'));
        $this->assertNull(collect(NavigationMenu::for(User::factory()->create()))->firstWhere('item.key', 'data'));
    }
}
