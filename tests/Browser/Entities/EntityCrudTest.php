<?php

namespace Entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use Entities13\Models\Entity;
use Entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EntityCrudTest extends DuskTestCase
{
    private const ENTITY_NAME_SELECTOR = 'dialog[open] [data-test="entity-name"]';

    private const ENTITY_NOTES_SELECTOR = 'dialog[open] [data-test="entity-notes"]';

    private const ENTITY_SUBMIT_SELECTOR = 'dialog[open] [data-test="entity-submit"]';

    private const ENTITY_STATUS_SELECTOR = '[data-test="entity-status"]';

    use DatabaseMigrations;

    public function test_entity_row_name_opens_the_edit_form(): void
    {
        $user = $this->user();
        $entity = Entity::factory()->create(['name' => 'Editable from the row']);

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-name-'.$entity->id.'"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->assertSeeIn('dialog[open]', __('Edit entity'))
                ->assertInputValue(self::ENTITY_NAME_SELECTOR, $entity->name);
        });
    }

    public function test_entity_table_keeps_the_actions_column_stuck_to_the_end(): void
    {
        $user = $this->user();
        $longName = 'Very long entity name '.str_repeat('x', 120);
        $entity = Entity::factory()->create(['name' => $longName]);

        $this->browse(function (Browser $browser) use ($user, $entity, $longName): void {
            $browser->loginAs($user)
                ->resize(1920, 1080)
                ->visit('/entities?search='.urlencode($longName));

            $browser->assertAttribute('[data-test="entity-name-'.$entity->id.'"]', 'title', $longName)
                ->assertScript(
                    'getComputedStyle(document.querySelector(\'.resource-list-table tbody tr td:last-child\')).position',
                    'sticky',
                )
                ->assertScript(
                    'getComputedStyle(document.querySelector(\'.resource-list-table tbody tr td:last-child\')).textAlign',
                    'end',
                )
                ->resize(320, 420)
                ->assertScript(
                    'getComputedStyle(document.querySelector(\'[data-test="entity-name-'.$entity->id.'"]\')).textOverflow',
                    'ellipsis',
                );
        });
    }

    public function test_entity_can_be_created_updated_and_deleted_from_the_drawer(): void
    {
        $user = $this->user();
        $entityName = 'Dusk entity '.fake()->uuid();
        $updatedEntityName = 'Updated entity '.fake()->uuid();
        $entityId = null;

        $this->browse(function (Browser $browser) use (
            $user,
            $entityName,
            $updatedEntityName,
            &$entityId,
        ): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->type(self::ENTITY_NAME_SELECTOR, $entityName)
                ->type(self::ENTITY_NOTES_SELECTOR, 'Test notes')
                ->click(self::ENTITY_SUBMIT_SELECTOR)
                ->waitFor(self::ENTITY_STATUS_SELECTOR)
                ->assertSee($entityName);

            $entity = Entity::query()->where('name', $entityName)->firstOrFail();
            $entityId = $entity->id;

            $browser->click("[data-test='entity-edit-{$entity->id}']")
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->clear(self::ENTITY_NAME_SELECTOR)
                ->type(self::ENTITY_NAME_SELECTOR, $updatedEntityName)
                ->click(self::ENTITY_SUBMIT_SELECTOR)
                ->waitFor(self::ENTITY_STATUS_SELECTOR)
                ->assertSee($updatedEntityName)
                ->click("[data-test='entity-delete-{$entity->id}']")
                ->waitFor('dialog[open] [data-test="entity-confirm-submit"]')
                ->click('dialog[open] [data-test="entity-confirm-submit"]')
                ->waitFor(self::ENTITY_STATUS_SELECTOR)
                ->assertDontSee($updatedEntityName);
        });

        $this->assertNotNull($entityId);
        $this->assertSoftDeleted('ENT_entities', ['id' => $entityId]);
    }

    public function test_entity_creation_shows_conflict_modal_for_trashed_name(): void
    {
        $user = $this->user();
        $entity = Entity::factory()->create(['name' => 'Reused entity']);
        $entity->delete();

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->type(self::ENTITY_NAME_SELECTOR, $entity->name)
                ->click(self::ENTITY_SUBMIT_SELECTOR)
                ->waitFor('dialog[open] [data-test="entity-conflict-restore"]')
                ->assertSee(__('Record Name already in trash'))
                ->assertSee(__('A deleted record already uses the name :name.', ['name' => $entity->name]))
                ->click('[data-test="entity-conflict-restore"]')
                ->waitFor(self::ENTITY_STATUS_SELECTOR)
                ->assertSee(__('basics13::messages.restored'));
        });

        $this->assertModelExists($entity);
    }
}
