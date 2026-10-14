<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Customers13\Contracts\CustomerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Tests\Fixtures\Contracts\CustomerWithChildrenIntegration;

/**
 * What hangs from a customer is the business of whichever package knows about it, so this package
 * only reads the one row key the integration reports and turns the delete action into an archive
 * one. The stand-in integration exercises that contract on its own terms; the integration package
 * exercises it with real records.
 */
class CustomerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_row_that_has_something_hanging_from_it_offers_archive_instead_of_delete(): void
    {
        $this->app->instance(CustomerIntegration::class, new CustomerWithChildrenIntegration);

        $customer = Customer::factory()->create();

        $response = $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]))->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('data-test="customer-archive-'.$customer->id.'"', false);
        $response->assertDontSee('data-test="customer-delete-'.$customer->id.'"', false);
        $response->assertSee(__('basics13::messages.archive_instead_of_delete'));
    }
}
