<?php

namespace Entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use Entities13\Models\Entity;
use Entities13\Models\EntityType;
use Entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The entity form picks types from a checkbox list, which is the control the browser posts and the
 * Alpine model binds to. These tests cover the control itself: what it offers, how tall it is, what
 * it keeps checked once the form is bound to a entity, and what reaches the pivot table.
 */
class EntityTypePickerTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const PICKER = 'dialog[open] [data-test="entity-type-ids"]';

    private const NAME_INPUT = 'dialog[open] [data-test="entity-name"]';

    private const SUBMIT = 'dialog[open] [data-test="entity-submit"]';

    private const STATUS = '[data-test="entity-status"]';

    public function test_create_only_shows_active_types(): void
    {
        $user = $this->user();
        $active = EntityType::factory()->create(['name_eu' => 'Active kind']);
        $archived = EntityType::factory()->archived()->create(['name_eu' => 'Archived kind']);
        $trashed = EntityType::factory()->trashed()->create(['name_eu' => 'Trashed kind']);

        $this->browse(function (Browser $browser) use ($user, $active, $archived, $trashed): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::PICKER)
                ->assertScript($this->typeIsVisible($active->id))
                ->assertScript($this->typeIsVisible($archived->id), false)
                ->assertScript($this->pickerHas($trashed->id), false)
                ->assertSeeIn(self::PICKER, 'Active kind');
        });
    }

    public function test_the_picker_says_so_when_there_is_no_type_to_pick(): void
    {
        $user = $this->user();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::PICKER)
                ->assertSeeIn(self::PICKER, __('No entity types yet.'))
                ->assertScript('document.querySelectorAll(\''.self::PICKER.' input[type="checkbox"]\').length', 0);
        });
    }

    public function test_the_picker_fits_a_short_list_without_scrolling(): void
    {
        $user = $this->user();
        EntityType::factory()->count(2)->create();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::PICKER)
                ->assertScript($this->pickerScrolls(), false);
        });
    }

    public function test_the_picker_scrolls_once_the_list_outgrows_the_drawer(): void
    {
        $user = $this->user();
        EntityType::factory()->count(25)->create();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::PICKER)
                ->assertScript('document.querySelectorAll(\''.self::PICKER.' input[type="checkbox"]\').length', 25)
                ->assertScript($this->pickerScrolls(), true);
        });
    }

    public function test_a_entity_can_be_created_with_two_types_picked(): void
    {
        $user = $this->user();
        $types = EntityType::factory()->count(3)->create();
        $entityName = 'Dusk typed entity '.fake()->uuid();

        $this->browse(function (Browser $browser) use ($user, $types, $entityName): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-create-button"]')
                ->waitFor(self::PICKER)
                ->type(self::NAME_INPUT, $entityName)
                ->check($this->typeInput($types[0]->id))
                ->check($this->typeInput($types[2]->id))
                ->assertChecked($this->typeInput($types[0]->id))
                ->assertChecked($this->typeInput($types[2]->id))
                ->assertNotChecked($this->typeInput($types[1]->id))
                ->waitUntil('document.querySelector(\''.self::SUBMIT.'\').disabled === false', 10)
                ->click(self::SUBMIT)
                ->waitFor(self::STATUS)
                ->assertSee($entityName);
        });

        $entity = Entity::query()->where('name', $entityName)->firstOrFail();
        $this->assertSame(
            [$types[0]->id, $types[2]->id],
            $entity->entityTypes()->orderBy('entity_type_id')->pluck('entity_type_id')->all(),
        );
    }

    public function test_edit_keeps_selected_archived_types_and_hides_other_archived_types(): void
    {
        $user = $this->user();
        $active = EntityType::factory()->create();
        $selectedArchived = EntityType::factory()->archived()->create();
        $otherArchived = EntityType::factory()->archived()->create();
        $entity = Entity::factory()->create(['name' => 'Entity holding two types']);
        $entity->entityTypes()->attach([$active->id, $selectedArchived->id]);

        $this->browse(function (Browser $browser) use ($user, $entity, $active, $selectedArchived, $otherArchived): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->click('[data-test="entity-edit-'.$entity->id.'"]')
                ->waitFor(self::PICKER)
                ->waitUntil($this->typeIsChecked($active->id), 10)
                ->assertScript($this->typeIsVisible($active->id))
                ->assertScript($this->typeIsVisible($selectedArchived->id))
                ->assertScript($this->typeIsVisible($otherArchived->id), false)
                ->assertChecked($this->typeInput($active->id))
                ->assertChecked($this->typeInput($selectedArchived->id))
                ->assertScript(
                    'document.querySelector('.json_encode($this->typeInput($selectedArchived->id))
                        .').closest("label").innerText.includes('.json_encode(__('basics13::messages.archived_label')).')',
                    true,
                );
        });
    }

    /**
     * A type is checked by the value of its box, which is the id as a string: the same value the
     * edit payload binds and the form posts.
     */
    private function typeInput(int $typeId): string
    {
        return self::PICKER.' input[value="'.$typeId.'"]';
    }

    private function pickerHas(int $typeId): string
    {
        return 'document.querySelector('.json_encode($this->typeInput($typeId)).') !== null';
    }

    private function typeIsChecked(int $typeId): string
    {
        return 'document.querySelector('.json_encode($this->typeInput($typeId)).').checked === true';
    }

    private function typeIsVisible(int $typeId): string
    {
        return 'getComputedStyle(document.querySelector('.json_encode($this->typeInput($typeId))
            .').closest("label")).display !== "none"';
    }

    private function pickerScrolls(): string
    {
        return 'document.querySelector('.json_encode(self::PICKER).').scrollHeight'
            .' > document.querySelector('.json_encode(self::PICKER).').clientHeight';
    }
}
