<?php

namespace Entities13\Tests\Browser\Entities;

use Laravel\Dusk\Browser;
use Entities13\Models\EntityType;
use Entities13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The entity type drawer is filled in Alpine from the row payload, so the four names have to follow
 * the record being edited and the write has to reach that record only.
 */
class EntityTypeCrudTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const NAMES = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    private const SUBMIT_SELECTOR = 'dialog[open] [data-test="entity-type-submit"]';

    private const STATUS_SELECTOR = '[data-test="entity-type-status"]';

    public function test_the_drawer_follows_the_entity_type_being_edited(): void
    {
        $user = $this->admin();
        $first = EntityType::factory()->create($this->names('First'));
        $second = EntityType::factory()->create($this->names('Second'));

        $this->browse(function (Browser $browser) use ($user, $first, $second): void {
            $browser->loginAs($user)
                ->visit('/entity-types')
                ->click("[data-test='entity-type-edit-{$first->getKey()}']")
                ->waitFor($this->field('name_eu'));

            $this->assertNames($browser, 'First');

            $browser->visit('/entity-types')
                ->click("[data-test='entity-type-edit-{$second->getKey()}']")
                ->waitFor($this->field('name_eu'));

            $this->assertNames($browser, 'Second');

            $browser->clear($this->field('name_es'))
                ->type($this->field('name_es'), 'Second renamed')
                ->click(self::SUBMIT_SELECTOR)
                ->waitFor(self::STATUS_SELECTOR);
        });

        $this->assertSame('Second renamed', $second->refresh()->name_es);
        $this->assertSame('First es', $first->refresh()->name_es);
    }

    public function test_an_entity_type_can_be_created_and_deleted_from_the_drawer(): void
    {
        $user = $this->admin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/entity-types')
                ->click('[data-test="entity-type-create-button"]')
                ->waitFor($this->field('name_eu'));

            foreach ($this->names('Created') as $field => $value) {
                $browser->type($this->field($field), $value);
            }

            $browser->click(self::SUBMIT_SELECTOR)
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.created'));

            $created = EntityType::query()->where('name_eu', 'Created eu')->firstOrFail();

            $browser->click("[data-test='entity-type-delete-{$created->getKey()}']")
                ->waitFor('dialog[open] [data-test="entity-type-confirm-submit"]')
                ->click('dialog[open] [data-test="entity-type-confirm-submit"]')
                ->waitForTextIn(self::STATUS_SELECTOR, __('basics13::messages.moved_to_trash'));

            $this->assertSoftDeleted($created);
        });
    }

    /**
     * @return array<string, string>
     */
    private function names(string $prefix): array
    {
        $names = [];

        foreach (self::NAMES as $field) {
            $names[$field] = $prefix.' '.substr($field, 5);
        }

        return $names;
    }

    private function assertNames(Browser $browser, string $prefix): void
    {
        foreach ($this->names($prefix) as $field => $value) {
            $browser->assertInputValue($this->field($field), $value);
        }
    }

    private function field(string $field): string
    {
        return 'dialog[open] [data-test="entity-type-'.$field.'"]';
    }
}
