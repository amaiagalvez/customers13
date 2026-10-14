<?php

namespace Customers13\Tests\Browser\Customers;

use Laravel\Dusk\Browser;
use Customers13\Models\Customer;
use Customers13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CustomerFormTest extends DuskTestCase
{
    private const CUSTOMER_NAME_SELECTOR = 'dialog[open] [data-test="customer-name"]';

    private const CUSTOMER_NOTES_SELECTOR = 'dialog[open] [data-test="customer-notes"]';

    private const CUSTOMER_SUBMIT_SELECTOR = 'dialog[open] [data-test="customer-submit"]';

    private const CUSTOMER_STATUS_SELECTOR = '[data-test="customer-status"]';

    use DatabaseMigrations;

    public function test_customer_form_prefills_data_when_editing(): void
    {
        $user = $this->user();
        $customer = Customer::factory()->create([
            'name' => 'Existing customer',
            'notes' => 'Existing notes',
        ]);

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click("[data-test='customer-edit-{$customer->id}']")
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->assertInputValue(self::CUSTOMER_NAME_SELECTOR, $customer->name)
                ->assertInputValue(self::CUSTOMER_NOTES_SELECTOR, $customer->notes)
                ->assertSeeIn('dialog[open]', __('Edit customer'));
        });
    }

    public function test_customer_form_clears_when_cancelled(): void
    {
        $user = $this->user();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->type(self::CUSTOMER_NAME_SELECTOR, 'Test customer')
                ->type(self::CUSTOMER_NOTES_SELECTOR, 'Test notes')
                ->click('dialog[open] [data-test="customer-cancel"]')
                ->assertDialogOpened(__('You have unsaved changes. Leave without saving?'))
                ->acceptDialog()
                ->waitUntilMissing(self::CUSTOMER_NAME_SELECTOR)
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->assertInputValue(self::CUSTOMER_NAME_SELECTOR, '')
                ->assertInputValue(self::CUSTOMER_NOTES_SELECTOR, '');
        });
    }

    public function test_customer_form_handles_long_notes(): void
    {
        $user = $this->user();
        $longNotes = str_repeat('Long notes content. ', 100);

        $this->browse(function (Browser $browser) use ($user, $longNotes): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->click('[data-test="customer-create-button"]')
                ->waitFor(self::CUSTOMER_NAME_SELECTOR)
                ->type(self::CUSTOMER_NAME_SELECTOR, 'Customer with long notes')
                ->type(self::CUSTOMER_NOTES_SELECTOR, $longNotes)
                ->click(self::CUSTOMER_SUBMIT_SELECTOR)
                ->waitFor(self::CUSTOMER_STATUS_SELECTOR)
                ->assertSee('Customer with long notes');
        });

        $this->assertDatabaseHas('CUM_customers', ['name' => 'Customer with long notes']);
    }
}
