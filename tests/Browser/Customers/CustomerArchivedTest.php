<?php

namespace Customers13\Tests\Browser\Customers;

use Laravel\Dusk\Browser;
use Customers13\Models\Customer;
use Customers13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CustomerArchivedTest extends DuskTestCase
{
    private const CUSTOMER_CONFIRM_SUBMIT_SELECTOR = 'dialog[open] [data-test="customer-confirm-submit"]';

    use DatabaseMigrations;

    public function test_archived_customers_are_not_shown_in_the_active_list(): void
    {
        $user = $this->user();

        Customer::factory()->archived()->create(['name' => 'Dormant customer']);
        Customer::factory()->create(['name' => 'Active customer']);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers')
                ->assertDontSee('Dormant customer')
                ->assertSee('Active customer');
        });
    }

    public function test_archived_customers_are_searchable_by_name(): void
    {
        $user = $this->user();

        $this->travelTo('2026-10-02 12:30:00');
        $updatedCustomer = Customer::factory()->archived()->create(['name' => 'Alpha Dormant']);
        Customer::factory()->archived()->create(['name' => 'Beta Dormant']);
        Customer::factory()->create(['name' => 'Alpha Active']);
        $this->travelTo('2026-07-15 12:30:00');
        $updatedCustomer->touch();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers/archived?search=Alpha')
                ->assertSee('Alpha Dormant')
                ->assertDontSee('Beta Dormant')
                ->assertDontSee('Alpha Active');
        });
    }

    public function test_an_archived_customer_can_be_reactivated(): void
    {
        // Seeding the archived state keeps this package free of the bridge: the archive *button*
        // only appears for a customer that owns projects, which is covered by CustomerProjects13.
        $customer = Customer::factory()->archived()->create(['name' => 'Customer to reactivate']);
        $user = $this->user();

        $this->assertFalse($customer->active);

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $browser->loginAs($user)
                ->visit('/customers/archived')
                ->assertSee($customer->name)
                ->click("[data-test='customer-activate-{$customer->id}']")
                ->waitFor(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="customer-status"]')
                ->assertSee(__('basics13::messages.activated'));

            $this->assertTrue($customer->refresh()->active);
        });
    }

    public function test_restoring_a_trashed_archived_customer_preserves_its_archived_state(): void
    {
        $customer = Customer::factory()->archived()->trashed()->create(['name' => 'Archived and trashed']);
        $user = $this->user();

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $browser->loginAs($user)
                ->visit('/customers/trash')
                ->click("[data-test='customer-restore-{$customer->id}']")
                ->waitFor(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="customer-status"]')
                ->assertSee(__('basics13::messages.restored'));
        });

        $this->assertNotSoftDeleted($customer);
        $this->assertFalse($customer->refresh()->active);
    }
}
