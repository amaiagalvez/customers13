<?php

namespace Customers13\Tests\Browser\Customers;

use Laravel\Dusk\Browser;
use Customers13\Models\Customer;
use Customers13\Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CustomerTrashTest extends DuskTestCase
{
    private const CUSTOMER_CONFIRM_SUBMIT_SELECTOR = 'dialog[open] [data-test="customer-confirm-submit"]';

    private const CUSTOMER_ERROR_SELECTOR = '[data-test="customer-error"]';

    use DatabaseMigrations;

    public function test_trash_list_searches_deleted_customers_without_showing_live_records(): void
    {
        $user = $this->user();

        Customer::factory()->trashed()->create(['name' => 'Matching deleted customer']);
        Customer::factory()->trashed()->create(['name' => 'Other deleted customer']);
        Customer::factory()->create(['name' => 'Matching active customer']);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/customers/trash?search=Matching')
                ->assertSee('Matching deleted customer')
                ->assertDontSee('Other deleted customer')
                ->assertDontSee('Matching active customer');
        });
    }

    public function test_customer_can_be_restored_and_permanently_deleted(): void
    {
        $user = $this->user();
        $customer = Customer::factory()->create(['name' => 'Customer to restore and delete']);

        $this->browse(function (Browser $browser) use ($user, $customer): void {
            $customer->delete();

            $browser->loginAs($user)
                ->visit('/customers/trash')
                ->click("[data-test='customer-restore-{$customer->id}']")
                ->waitFor(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="customer-status"]')
                ->assertSee(__('basics13::messages.restored'));

            $this->assertNotSoftDeleted($customer);

            $customer->refresh()->delete();

            $browser->visit('/customers/trash')
                ->click("[data-test='customer-force-delete-{$customer->id}']")
                ->waitFor(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor('[data-test="customer-status"]')
                ->assertSee(__('basics13::messages.permanently_deleted'));
        });

        $this->assertModelMissing($customer);
    }

    public function test_restore_is_rejected_when_an_active_customer_has_reused_the_name(): void
    {
        $user = $this->user();

        $trashedCustomer = Customer::factory()->create(['name' => 'Reused customer']);
        $trashedCustomer->delete();
        $activeCustomer = Customer::factory()->create(['name' => 'Reused customer']);

        $this->browse(function (Browser $browser) use ($user, $trashedCustomer): void {
            $browser->loginAs($user)
                ->visit('/customers/trash')
                ->click("[data-test='customer-restore-{$trashedCustomer->id}']")
                ->waitFor(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->click(self::CUSTOMER_CONFIRM_SUBMIT_SELECTOR)
                ->waitFor(self::CUSTOMER_ERROR_SELECTOR)
                ->assertSee(__('basics13::messages.cannot_restore_name_taken'));
        });

        $this->assertSoftDeleted($trashedCustomer);
        $this->assertModelExists($activeCustomer);
    }
}
