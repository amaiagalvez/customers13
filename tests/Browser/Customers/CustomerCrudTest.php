<?php

namespace Customers13\Tests\Browser\Customers;

use Laravel\Dusk\Browser;
use Customers13\Models\Customer;
use Customers13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CustomerCrudTest extends DuskTestCase
{
    private const CUSTOMER_NAME_SELECTOR = 'dialog[open] [data-test="customer-name"]';

    private const CUSTOMER_NOTES_SELECTOR = 'dialog[open] [data-test="customer-notes"]';

    private const CUSTOMER_SUBMIT_SELECTOR = 'dialog[open] [data-test="customer-submit"]';

    private const CUSTOMER_STATUS_SELECTOR = '[data-test="customer-status"]';

    use DatabaseMigrations;

    public function test_customer_row_name_opens_the_edit_form(): void
    {
        $user = $this->user();
        $customer = Customer::factory()->create(['name' => 'Editable from the row']);

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-name-'.$customer->id.'"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->assertSeeIn('dialog[open]', __('Edit customer'))
                ->assertInputValue(self::CUSTOMER_NAME_SELECTOR, $customer->name);
        });
    }

    public function test_customer_table_keeps_the_actions_column_stuck_to_the_end(): void
    {
        $user = $this->user();
        $longName = 'Very long customer name '.str_repeat('x', 120);
        $customer = Customer::factory()->create(['name' => $longName]);

        $this->browse(function (Browser $browser) use ($user, $customer, $longName): void {
            $browser->loginAs($user)
                ->resize(1920, 1080)
                ->visit('/customers?search='.urlencode($longName));

            $browser->assertAttribute('[data-test="customer-name-'.$customer->id.'"]', 'title', $longName)
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
                    'getComputedStyle(document.querySelector(\'[data-test="customer-name-'.$customer->id.'"]\')).textOverflow',
                    'ellipsis',
                );
        });
    }

    public function test_customer_can_be_created_updated_and_deleted_from_the_drawer(): void
    {
        $user = $this->user();
        $customerName = 'Dusk customer '.fake()->uuid();
        $updatedCustomerName = 'Updated customer '.fake()->uuid();
        $customerId = null;

        $this->browse(function (Browser $browser) use (
            $user,
            $customerName,
            $updatedCustomerName,
            &$customerId,
        ): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->type(self::CUSTOMER_NAME_SELECTOR, $customerName)
                ->type(self::CUSTOMER_NOTES_SELECTOR, 'Test notes')
                ->click(self::CUSTOMER_SUBMIT_SELECTOR)
                ->waitFor(self::CUSTOMER_STATUS_SELECTOR)
                ->assertSee($customerName);

            $customer = Customer::query()->where('name', $customerName)->firstOrFail();
            $customerId = $customer->id;

            $browser->click("[data-test='customer-edit-{$customer->id}']")
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->clear(self::CUSTOMER_NAME_SELECTOR)
                ->type(self::CUSTOMER_NAME_SELECTOR, $updatedCustomerName)
                ->click(self::CUSTOMER_SUBMIT_SELECTOR)
                ->waitFor(self::CUSTOMER_STATUS_SELECTOR)
                ->assertSee($updatedCustomerName)
                ->click("[data-test='customer-delete-{$customer->id}']")
                ->waitFor('dialog[open] [data-test="customer-confirm-submit"]')
                ->click('dialog[open] [data-test="customer-confirm-submit"]')
                ->waitFor(self::CUSTOMER_STATUS_SELECTOR)
                ->assertDontSee($updatedCustomerName);
        });

        $this->assertNotNull($customerId);
        $this->assertSoftDeleted('CUM_customers', ['id' => $customerId]);
    }

    public function test_customer_creation_shows_conflict_modal_for_trashed_name(): void
    {
        $user = $this->user();
        $customer = Customer::factory()->create(['name' => 'Reused customer']);
        $customer->delete();

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->type(self::CUSTOMER_NAME_SELECTOR, $customer->name)
                ->click(self::CUSTOMER_SUBMIT_SELECTOR)
                ->waitFor('dialog[open] [data-test="customer-conflict-restore"]')
                ->assertSee(__('Record Name already in trash'))
                ->assertSee(__('A deleted record already uses the name :name.', ['name' => $customer->name]))
                ->click('[data-test="customer-conflict-restore"]')
                ->waitFor(self::CUSTOMER_STATUS_SELECTOR)
                ->assertSee(__('basics13::messages.restored'));
        });

        $this->assertModelExists($customer);
    }
}
