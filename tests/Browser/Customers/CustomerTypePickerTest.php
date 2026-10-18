<?php

namespace Customers13\Tests\Browser\Customers;

use Laravel\Dusk\Browser;
use Customers13\Models\Customer;
use Customers13\Tests\DuskTestCase;
use Customers13\Models\CustomerType;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The customer form picks types from a checkbox list, which is the control the browser posts and the
 * Alpine model binds to. These tests cover the control itself: what it offers, how tall it is, what
 * it keeps checked once the form is bound to a customer, and what reaches the pivot table.
 */
class CustomerTypePickerTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const PICKER = 'dialog[open] [data-test="customer-type-ids"]';

    private const NAME_INPUT = 'dialog[open] [data-test="customer-name"]';

    private const SUBMIT = 'dialog[open] [data-test="customer-submit"]';

    private const STATUS = '[data-test="customer-status"]';

    public function test_create_only_shows_active_types(): void
    {
        $user = $this->user();
        $active = CustomerType::factory()->create(['name_eu' => 'Active kind']);
        $archived = CustomerType::factory()->archived()->create(['name_eu' => 'Archived kind']);
        $trashed = CustomerType::factory()->trashed()->create(['name_eu' => 'Trashed kind']);

        $this->browse(function (Browser $browser) use ($user, $active, $archived, $trashed): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
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
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::PICKER)
                ->assertSeeIn(self::PICKER, __('No customer types yet.'))
                ->assertScript('document.querySelectorAll(\''.self::PICKER.' input[type="checkbox"]\').length', 0);
        });
    }

    public function test_the_picker_fits_a_short_list_without_scrolling(): void
    {
        $user = $this->user();
        CustomerType::factory()->count(2)->create();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::PICKER)
                ->assertScript($this->pickerScrolls(), false);
        });
    }

    public function test_the_picker_scrolls_once_the_list_outgrows_the_drawer(): void
    {
        $user = $this->user();
        CustomerType::factory()->count(25)->create();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::PICKER)
                ->assertScript('document.querySelectorAll(\''.self::PICKER.' input[type="checkbox"]\').length', 25)
                ->assertScript($this->pickerScrolls(), true);
        });
    }

    public function test_a_customer_can_be_created_with_two_types_picked(): void
    {
        $user = $this->user();
        $types = CustomerType::factory()->count(3)->create();
        $customerName = 'Dusk typed customer '.fake()->uuid();

        $this->browse(function (Browser $browser) use ($user, $types, $customerName): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::PICKER)
                ->type(self::NAME_INPUT, $customerName)
                ->check($this->typeInput($types[0]->id))
                ->check($this->typeInput($types[2]->id))
                ->assertChecked($this->typeInput($types[0]->id))
                ->assertChecked($this->typeInput($types[2]->id))
                ->assertNotChecked($this->typeInput($types[1]->id))
                ->waitUntil('document.querySelector(\''.self::SUBMIT.'\').disabled === false', 10)
                ->click(self::SUBMIT)
                ->waitFor(self::STATUS)
                ->assertSee($customerName);
        });

        $customer = Customer::query()->where('name', $customerName)->firstOrFail();
        $this->assertSame(
            [$types[0]->id, $types[2]->id],
            $customer->customerTypes()->orderBy('customer_type_id')->pluck('customer_type_id')->all(),
        );
    }

    public function test_edit_keeps_selected_archived_types_and_hides_other_archived_types(): void
    {
        $user = $this->user();
        $active = CustomerType::factory()->create();
        $selectedArchived = CustomerType::factory()->archived()->create();
        $otherArchived = CustomerType::factory()->archived()->create();
        $customer = Customer::factory()->create(['name' => 'Customer holding two types']);
        $customer->customerTypes()->attach([$active->id, $selectedArchived->id]);

        $this->browse(function (Browser $browser) use ($user, $customer, $active, $selectedArchived, $otherArchived): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-edit-'.$customer->id.'"]')
                ->waitFor(self::PICKER)
                ->waitUntil($this->typeIsChecked($active->id), 10)
                ->assertScript($this->typeIsVisible($active->id))
                ->assertScript($this->typeIsVisible($selectedArchived->id))
                ->assertScript($this->typeIsVisible($otherArchived->id), false)
                ->assertChecked($this->typeInput($active->id))
                ->assertChecked($this->typeInput($selectedArchived->id));
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
