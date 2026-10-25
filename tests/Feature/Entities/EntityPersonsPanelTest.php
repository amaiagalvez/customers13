<?php

namespace Entities13\Tests\Feature\Entities;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityPersonsPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_entity_list_carries_the_active_persons_for_the_edit_drawer(): void
    {
        $this->actingAs($this->createAdminUser());
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create(['name' => 'Ane', 'last_name_1' => 'Etxeberria']);
        Person::factory()->forEntity($entity)->archived()->create(['name' => 'Jon', 'last_name_1' => 'Aldasoro']);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee('data-test="entity-persons-panel"', false)
            ->assertSee('Ane Etxeberria')
            ->assertDontSee('Jon Aldasoro');
    }

    public function test_the_panel_offers_the_persons_list_for_creating_and_editing(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee('data-test="entity-persons-panel-create"', false)
            ->assertSee('aria-label="'.__('Edit person').'"', false);
    }
}
