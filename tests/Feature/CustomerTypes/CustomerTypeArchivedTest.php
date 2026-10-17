<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Customers13\Models\CustomerType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CustomerTypeArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_types_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->archived()->create(['name_eu' => 'Mota artxibatua']);
        CustomerType::factory()->create(['name_eu' => 'Mota aktiboa']);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertDontSee('Mota artxibatua')
            ->assertSee('Mota aktiboa');
    }

    public function test_archived_types_are_searchable_by_any_of_their_names(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->archived()->create([
            'name_eu' => 'Alpha mota',
            'name_es' => 'Beta tipo',
            'name_fr' => 'Gamma type',
            'name_en' => 'Delta kind',
        ]);
        CustomerType::factory()->archived()->create(['name_eu' => 'Zeta mota']);

        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $term) {
            $this->get(route('customer-types.archived.index', ['search' => $term]))
                ->assertOk()
                ->assertSee('Alpha mota')
                ->assertDontSee('Zeta mota');
        }
    }

    public function test_a_type_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();

        $this->patch(route('customer-types.archive', $customerType))
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($customerType->refresh()->active);

        $this->patch(route('customer-types.archived.activate', $customerType))
            ->assertRedirect(route('customer-types.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($customerType->refresh()->active);
    }

    public function test_an_archived_type_held_by_a_customer_can_be_activated_again(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->archived()->create();
        Customer::factory()->create()->customerTypes()->attach($customerType);

        $this->patch(route('customer-types.archived.activate', $customerType))
            ->assertRedirect(route('customer-types.archived.index'));

        $this->assertTrue($customerType->refresh()->active);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
    }
}
