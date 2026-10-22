<?php

namespace Entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use Entities13\Models\Entity;
use Entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EntityFormTest extends DuskTestCase
{
    private const ENTITY_NAME_SELECTOR = 'dialog[open] [data-test="entity-name"]';

    private const ENTITY_NOTES_SELECTOR = 'dialog[open] [data-test="entity-notes"]';

    private const ENTITY_SUBMIT_SELECTOR = 'dialog[open] [data-test="entity-submit"]';

    private const ENTITY_STATUS_SELECTOR = '[data-test="entity-status"]';

    use DatabaseMigrations;

    public function test_entity_form_prefills_data_when_editing(): void
    {
        $user = $this->admin();
        $entity = Entity::factory()->create([
            'name' => 'Existing entity',
            'notes' => 'Existing notes',
        ]);

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click("[data-test='entity-edit-{$entity->id}']")
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->assertInputValue(self::ENTITY_NAME_SELECTOR, $entity->name)
                ->assertInputValue(self::ENTITY_NOTES_SELECTOR, $entity->notes)
                ->assertSeeIn('dialog[open]', __('Edit entity'));
        });
    }

    public function test_entity_form_clears_when_cancelled(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->type(self::ENTITY_NAME_SELECTOR, 'Test entity')
                ->type(self::ENTITY_NOTES_SELECTOR, 'Test notes')
                ->click('dialog[open] [data-test="entity-cancel"]')
                ->assertDialogOpened(__('You have unsaved changes. Leave without saving?'))
                ->acceptDialog()
                ->waitUntilMissing(self::ENTITY_NAME_SELECTOR)
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->assertInputValue(self::ENTITY_NAME_SELECTOR, '')
                ->assertInputValue(self::ENTITY_NOTES_SELECTOR, '');
        });
    }

    public function test_entity_form_handles_long_notes(): void
    {
        $user = $this->admin();
        $longNotes = str_repeat('Long notes content. ', 100);

        $this->browse(function (Browser $browser) use ($user, $longNotes): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::ENTITY_NAME_SELECTOR)
                ->type(self::ENTITY_NAME_SELECTOR, 'Entity with long notes')
                ->type(self::ENTITY_NOTES_SELECTOR, $longNotes)
                ->click(self::ENTITY_SUBMIT_SELECTOR)
                ->waitFor(self::ENTITY_STATUS_SELECTOR)
                ->assertSee('Entity with long notes');
        });

        $this->assertDatabaseHas('ENT_entities', ['name' => 'Entity with long notes']);
    }
}
