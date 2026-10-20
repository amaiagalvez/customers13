<?php

namespace Entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use Entities13\Models\Entity;
use Entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EntityArchivedTest extends DuskTestCase
{
    private const ENTITY_CONFIRM_SUBMIT_SELECTOR = 'dialog[open] [data-test="entity-confirm-submit"]';

    use DatabaseMigrations;

    public function test_archived_entities_are_not_shown_in_the_active_list(): void
    {
        $user = $this->user();

        Entity::factory()->archived()->create(['name' => 'Dormant entity']);
        Entity::factory()->create(['name' => 'Active entity']);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities')
                ->assertDontSee('Dormant entity')
                ->assertSee('Active entity');
        });
    }

    public function test_archived_entities_are_searchable_by_name(): void
    {
        $user = $this->user();

        $this->travelTo('2026-10-02 12:30:00');
        $updatedEntity = Entity::factory()->archived()->create(['name' => 'Alpha Dormant']);
        Entity::factory()->archived()->create(['name' => 'Beta Dormant']);
        Entity::factory()->create(['name' => 'Alpha Active']);
        $this->travelTo('2026-07-15 12:30:00');
        $updatedEntity->touch();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities/archived?search=Alpha')
                ->assertSee('Alpha Dormant')
                ->assertDontSee('Beta Dormant')
                ->assertDontSee('Alpha Active');
        });
    }

    public function test_an_archived_entity_can_be_reactivated(): void
    {
        // Seeding the archived state keeps this package free of the bridge: the archive *button*
        // only appears for a entity that owns projects, which is covered by EntityProjects13.
        $entity = Entity::factory()->archived()->create(['name' => 'Entity to reactivate']);
        $user = $this->user();

        $this->assertFalse($entity->active);

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities/archived')
                ->assertSee($entity->name)
                ->click("[data-test='entity-activate-{$entity->id}']")
                ->waitFor(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="entity-status"]')
                ->assertSee(__('basics13::messages.activated'));

            $this->assertTrue($entity->refresh()->active);
        });
    }

    public function test_restoring_a_trashed_archived_entity_preserves_its_archived_state(): void
    {
        $entity = Entity::factory()->archived()->trashed()->create(['name' => 'Archived and trashed']);
        $user = $this->user();

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $browser->loginAs($user)
                ->visit('/entities/trash')
                ->click("[data-test='entity-restore-{$entity->id}']")
                ->waitFor(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="entity-status"]')
                ->assertSee(__('basics13::messages.restored'));
        });

        $this->assertNotSoftDeleted($entity);
        $this->assertFalse($entity->refresh()->active);
    }
}
