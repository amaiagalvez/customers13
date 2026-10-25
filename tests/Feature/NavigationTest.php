<?php

namespace Entities13\Tests\Feature;

use Entities13\Tests\TestCase;
use Basics13\Support\NavigationMenu;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\EntitiesAccessSeeder;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_sees_the_persons_and_person_types_links(): void
    {
        $this->assertContains('persons', $this->dataLinks($this->createAdminUser()));
        $this->assertContains('person-types', $this->dataLinks($this->createAdminUser()));
    }

    public function test_the_links_are_hidden_without_permission(): void
    {
        $links = $this->dataLinks($this->createUserWithPermissions([]));

        $this->assertNotContains('persons', $links);
        $this->assertNotContains('person-types', $links);
    }

    public function test_each_link_asks_for_its_view_permission(): void
    {
        $this->assertSame(['persons'], $this->dataLinks($this->createUserWithPermissions(['persons.view'])));
        $this->assertSame(['person-types'], $this->dataLinks($this->createUserWithPermissions(['person_types.view'])));
    }

    public function test_running_the_seeder_again_keeps_the_links(): void
    {
        $this->seed(EntitiesAccessSeeder::class);

        $this->assertContains('persons', $this->dataLinks($this->createAdminUser()));
    }

    /**
     * @return list<string>
     */
    private function dataLinks(Authenticatable $user): array
    {
        $group = collect(NavigationMenu::for($user))->firstWhere('item.key', 'entities13');
        $links = [];

        foreach ($group['children'] ?? [] as $child) {
            foreach ($child['children'] === [] ? [$child] : $child['children'] as $link) {
                $links[] = $link['item']['key'];
            }
        }

        return $links;
    }
}
