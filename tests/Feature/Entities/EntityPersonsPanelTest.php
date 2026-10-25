<?php

namespace Entities13\Tests\Feature\Entities;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityPersonsPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_edit_button_of_each_person_has_an_accessible_name(): void
    {
        $this->actingAs($this->createUserWithPermissions(['persons.view', 'persons.edit']));
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create();

        $html = view('entities13::entities.persons-panel', ['entity' => $entity->load('persons.personTypes')])->render();

        $this->assertStringContainsString('aria-label="'.__('Edit person').'"', $html);
    }
}
