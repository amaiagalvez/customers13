<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTypeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_hold_several_types(): void
    {
        $this->actingAs($this->user());
        $first = CustomerType::factory()->create(['name_eu' => 'Mota bat']);
        $second = CustomerType::factory()->create(['name_eu' => 'Mota bi']);

        $this->post(route('customers.store'), [
            'name' => 'Bezeroa',
            'customer_type_ids' => [$first->id, $second->id],
        ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->sole();
        $this->assertSame(
            [$first->id, $second->id],
            $customer->customerTypes()->orderBy('customer_type_id')->pluck('customer_type_id')->all(),
        );
    }

    public function test_the_customer_list_shows_the_types_of_each_customer(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create(['name_eu' => 'Mota ikusgai']);
        $customer = Customer::factory()->create(['name' => 'Bezeroa']);
        $customer->customerTypes()->attach($customerType);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee(__('Types'))
            ->assertSee('Mota ikusgai')
            ->assertSee('data-test="customer-types-'.$customer->id.'"', false);
    }

    public function test_the_edit_payload_carries_the_type_ids_as_strings(): void
    {
        $this->actingAs($this->user());
        $first = CustomerType::factory()->create();
        $second = CustomerType::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Bezeroa']);
        $customer->customerTypes()->attach([$first->id, $second->id]);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertViewHas('list', function (array $list) use ($first, $second): bool {
                return $list['rows'][0]['editPayload']['customer_type_ids'] === [
                    (string) $first->id,
                    (string) $second->id,
                ];
            });
    }

    public function test_the_customer_form_offers_every_type_that_can_be_picked(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->create(['name_eu' => 'Mota aktiboa']);
        CustomerType::factory()->archived()->create(['name_eu' => 'Mota artxibatua']);
        CustomerType::factory()->trashed()->create(['name_eu' => 'Mota ezabatua']);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('data-test="customer-type-ids"', false)
            ->assertSee('Mota aktiboa')
            ->assertSee('Mota artxibatua')
            ->assertDontSee('Mota ezabatua');
    }

    public function test_updating_a_customer_replaces_the_types_it_holds(): void
    {
        $this->actingAs($this->user());
        $held = CustomerType::factory()->create();
        $dropped = CustomerType::factory()->create();
        $added = CustomerType::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Bezeroa']);
        $customer->customerTypes()->attach([$held->id, $dropped->id]);

        $this->put(route('customers.update', $customer), [
            'name' => 'Bezeroa',
            'customer_type_ids' => [$held->id, $added->id],
        ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$held->id, $added->id],
            $customer->customerTypes()->orderBy('customer_type_id')->pluck('customer_type_id')->all(),
        );
    }

    public function test_an_update_that_posts_no_types_empties_the_set(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Bezeroa']);
        $customer->customerTypes()->attach($customerType);

        $this->put(route('customers.update', $customer), ['name' => 'Bezeroa'])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('CUM_customer_customer_type')
            ->where('customer_id', $customer->id)->count());
    }

    public function test_an_archived_type_the_customer_already_holds_stays_assigned(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Bezeroa']);
        $customer->customerTypes()->attach($customerType);

        $this->patch(route('customer-types.archive', $customerType))->assertRedirect();

        $this->put(route('customers.update', $customer), [
            'name' => 'Bezeroa',
            'customer_type_ids' => [$customerType->id],
        ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('CUM_customer_customer_type', [
            'customer_id' => $customer->id,
            'customer_type_id' => $customerType->id,
        ]);
    }

    public function test_an_archived_type_cannot_be_assigned_to_a_customer_that_does_not_hold_it(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->archived()->create();

        $this->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Bezeroa',
                'customer_type_ids' => [$customerType->id],
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors('customer_type_ids.0');

        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_a_trashed_type_cannot_be_assigned(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create();

        $this->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Bezeroa',
                'customer_type_ids' => [$customerType->id],
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors('customer_type_ids.0');

        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_a_type_that_does_not_exist_cannot_be_assigned(): void
    {
        $this->actingAs($this->user());

        $this->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Bezeroa',
                'customer_type_ids' => [999999],
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors('customer_type_ids.0');

        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_deleting_a_customer_keeps_its_types_reserved(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();
        $customer = Customer::factory()->create();
        $customer->customerTypes()->attach($customerType);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted($customer);
        $this->delete(route('customer-types.destroy', $customerType))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));
        $this->assertNotSoftDeleted($customerType);
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
