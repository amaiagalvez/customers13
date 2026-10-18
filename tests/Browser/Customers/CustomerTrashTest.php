<?php

namespace entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use entities13\Models\Entity;
use entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EntityTrashTest extends DuskTestCase
{
    private const ENTITY_CONFIRM_SUBMIT_SELECTOR = 'dialog[open] [data-test="entity-confirm-submit"]';

    private const ENTITY_ERROR_SELECTOR = '[data-test="entity-error"]';

    use DatabaseMigrations;

    public function test_trash_list_searches_deleted_entities_without_showing_live_records(): void
    {
        $user = $this->user();

        Entity::factory()->trashed()->create(['name' => 'Matching deleted entity']);
        Entity::factory()->trashed()->create(['name' => 'Other deleted entity']);
        Entity::factory()->create(['name' => 'Matching active entity']);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entities/trash?search=Matching')
                ->assertSee('Matching deleted entity')
                ->assertDontSee('Other deleted entity')
                ->assertDontSee('Matching active entity');
        });
    }

    public function test_entity_can_be_restored_and_permanently_deleted(): void
    {
        $user = $this->user();
        $entity = Entity::factory()->create(['name' => 'Entity to restore and delete']);

        $this->browse(function (Browser $browser) use ($user, $entity): void {
            $entity->delete();

            $browser->loginAs($user)
                ->visit('/entities/trash')
                ->click("[data-test='entity-restore-{$entity->id}']")
                ->waitFor(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="entity-status"]')
                ->assertSee(__('basics13::messages.restored'));

            $this->assertNotSoftDeleted($entity);

            $entity->refresh()->delete();

            $browser->visit('/entities/trash')
                ->click("[data-test='entity-force-delete-{$entity->id}']")
                ->waitFor(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="entity-status"]')
                ->assertSee(__('basics13::messages.permanently_deleted'));
        });

        $this->assertModelMissing($entity);
    }

    public function test_restore_is_rejected_when_an_active_entity_has_reused_the_name(): void
    {
        $user = $this->user();

        $trashedEntity = Entity::factory()->create(['name' => 'Reused entity']);
        $trashedEntity->delete();
        $activeEntity = Entity::factory()->create(['name' => 'Reused entity']);

        $this->browse(function (Browser $browser) use ($user, $trashedEntity): void {
            $browser->loginAs($user)
                ->visit('/entities/trash')
                ->click("[data-test='entity-restore-{$trashedEntity->id}']")
                ->waitFor(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::ENTITY_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor(self::ENTITY_ERROR_SELECTOR)
                ->assertSee(__('basics13::messages.cannot_restore_name_taken'));
        });

        $this->assertSoftDeleted($trashedEntity);
        $this->assertModelExists($activeEntity);
    }
}
