<?php

namespace entities13\Tests\Unit\Entities;

use entities13\Models\Entity;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function test_entity_fields_can_be_mass_assigned_without_a_database(): void
    {
        $entity = new Entity;

        $entity->fill([
            'name' => 'Ane Bezeroa',
            'notes' => 'Eskualdeko bezeroa',
        ]);

        $this->assertSame('Ane Bezeroa', $entity->getAttributes()['name']);
        $this->assertSame('Eskualdeko bezeroa', $entity->getAttributes()['notes']);
    }

    public function test_only_declared_entity_attributes_are_mass_assignable(): void
    {
        $entity = new Entity;

        $entity->fill([
            'name' => 'Ane Bezeroa',
            'notes' => 'Eskualdeko bezeroa',
            'id' => 42,
            'active' => false,
        ]);

        $this->assertSame('Ane Bezeroa', $entity->getAttributes()['name']);
        $this->assertTrue($entity->active);
        $this->assertFalse($entity->wasRecentlyCreated);
        $this->assertArrayNotHasKey('id', $entity->getAttributes());
    }

    public function test_a_new_entity_is_active_without_a_database(): void
    {
        $entity = new Entity;

        $this->assertTrue($entity->active);
    }
}
