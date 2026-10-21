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

    public function test_entity_row_name_is_not_interactive(): void
    {
        $user = $this->user();
        $entity = Entity::factory()->create(['name' => 'Editable from the row']);

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->assertSeeIn('[data-test="entity-name-'.$entity->id.'"]', $entity->name)
                ->assertScript(
                    'document.querySelector(\'[data-test="entity-name-'.$entity->id.'"]\').closest(\'a, button\') === null',
                    true,
                );
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

    public function test_advanced_search_button_and_form_are_correctly_displayed(): void
    {
        $user = $this->user();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->resize(375, 812)
                ->visit('/entities')
                ->assertVisible('[data-test="entity-advanced-search"]')
                ->assertSeeIn('[data-test="entity-advanced-search"]', __('basics13::messages.advanced_search'))
                ->assertScript(
                    '(() => { const bounds = document.querySelector(\'[data-test="entity-advanced-search"]\').getBoundingClientRect(); return bounds.width > 0 && bounds.left >= 0 && bounds.right <= window.innerWidth; })()',
                    true,
                )
                ->click('[data-test="entity-advanced-search"]')
                ->waitFor('dialog[open] #advanced-search-heading')
                ->assertSeeIn('dialog[open]', __('basics13::messages.advanced_search'))
                ->assertVisible('[data-test="advanced-search-close"]')
                ->assertAttribute(
                    '[data-test="advanced-search-close"]',
                    'aria-label',
                    __('basics13::messages.close'),
                )
                ->assertScript(
                    'document.querySelectorAll(\'dialog[open] [data-test="advanced-search-close"]\').length',
                    1,
                )
                ->assertScript(
                    'document.querySelectorAll(\'dialog[open] [data-flux-modal-close]\').length',
                    0,
                )
                ->assertVisible('dialog[open] input[wire\\:model="filters.name.value"]')
                ->assertVisible('[data-test="name-advanced-search-operator"]')
                ->assertScript(
                    'document.querySelector(\'[data-test="name-advanced-search-operator"]\').options.length > 0',
                    true,
                )
                ->assertScript(
                    'document.querySelector(\'[data-test="name-advanced-search-operator"]\').options[0].textContent.trim()',
                    __('basics13::messages.operator_contains'),
                )
                ->assertScript(
                    'document.querySelectorAll(\'dialog[open] input[type="date"]\').length',
                    2,
                )
                ->assertSeeIn('dialog[open]', 'Izena')
                ->assertSeeIn('dialog[open]', 'Sortze-data')
                ->assertSeeIn('dialog[open]', 'Egoera')
                ->assertSeeIn('dialog[open]', 'Bezero motak')
                ->assertDontSeeIn('dialog[open]', 'BASIC')
                ->assertDontSeeIn('dialog[open]', 'DATES')
                ->assertDontSeeIn('dialog[open]', 'STATUS')
                ->assertDontSeeIn('dialog[open]', 'RELATIONS')
                ->assertSeeIn('dialog[open]', __('basics13::messages.clear_filters'))
                ->assertSeeIn('dialog[open]', __('basics13::messages.apply_filters'))
                ->assertScript(
                    '(() => { const bounds = document.querySelector(\'dialog[open]\').getBoundingClientRect(); return bounds.width > 0 && bounds.left >= 0 && bounds.right <= window.innerWidth; })()',
                    true,
                )
                ->click('[data-test="advanced-search-toggle"]')
                ->waitForText('Oharrak')
                ->assertDontSeeIn('dialog[open]', 'entities13::messages.')
                ->click('[data-test="advanced-search-close"]')
                ->waitUntilMissing('dialog[open]');
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
