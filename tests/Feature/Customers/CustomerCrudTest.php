<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->post(route('customers.store'), ['name' => 'Guest customer'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_unverified_users_cannot_create_customers(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused']);

        $this->actingAs($user)
            ->post(route('customers.store'), ['name' => 'Unverified customer'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_empty_customer_list_shows_its_empty_state(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]))
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(__('No customers yet.'));
    }

    public function test_authenticated_users_can_create_update_and_delete_customers(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $response = $this->post(route('customers.store'), ['name' => 'Ane Bezeroa']);
        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('Ane Bezeroa', $customer->name);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('customer-create')
            ->assertSee('aria-labelledby="customer-form-heading"', false)
            ->assertSee('id="customer-form-heading"', false)
            ->assertSee('customer-edit-'.$customer->id);

        $this->put(route('customers.update', $customer), ['name' => 'Jon Bezeroa'])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('CUM_customers', ['id' => $customer->id, 'name' => 'Jon Bezeroa']);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($customer);
    }

    public function test_customer_store_persists_only_validated_attributes(): void
    {
        $user = $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->post(route('customers.store'), [
            'name' => 'Validated customer',
            'id' => 999999,
            'created_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('CUM_customers', [
            'name' => 'Validated customer',
        ]);
        $this->assertDatabaseMissing('CUM_customers', [
            'name' => 'Validated customer',
            'id' => 999999,
        ]);
        $this->assertDatabaseMissing('CUM_customers', [
            'name' => 'Validated customer',
            'created_at' => '2000-01-01 00:00:00',
        ]);
    }

    /**
     * Nothing in this package hangs from a customer, so the refusal comes from the hook an
     * integration package registers on the model. What this package owns is the outcome: the
     * customer stays put and the redirect says why.
     */
    public function test_a_refused_delete_reports_the_error_and_leaves_the_customer_out_of_the_trash(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $customer = Customer::factory()->create();
        Customer::deleting(static fn (): bool => false);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.'));

        $this->assertNotSoftDeleted($customer);
    }

    public function test_json_customer_creation_trims_the_name_and_returns_the_created_record(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->postJson(route('customers.store'), ['name' => '  Ane Bezeroa  '])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name'])
            ->assertJsonPath('name', 'Ane Bezeroa');

        $this->assertDatabaseHas('CUM_customers', ['name' => 'Ane Bezeroa']);
    }

    public function test_html_creation_opens_the_restore_conflict_for_a_trashed_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $customer = Customer::factory()->create(['name' => 'Reused customer']);
        $customer->delete();

        $this->post(route('customers.store'), ['name' => $customer->name])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('deleted_customer_conflict', [
                'id' => $customer->id,
                'name' => $customer->name,
            ]);

        $this->assertSame(0, Customer::query()->where('name', $customer->name)->count());
    }

    public function test_json_creation_requires_explicit_reuse_of_a_trashed_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $customer = Customer::factory()->create(['name' => 'Reused customer']);
        $customer->delete();

        $this->postJson(route('customers.store'), ['name' => $customer->name])
            ->assertConflict()
            ->assertJsonPath('errors.name.0', __('basics13::messages.deleted_name_conflict', ['name' => $customer->name]));

        $this->postJson(route('customers.store'), [
            'name' => $customer->name,
            'reuse_deleted_name' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('name', $customer->name);

        $this->assertSoftDeleted($customer);
        $this->assertSame(1, Customer::query()->where('name', $customer->name)->count());
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $name = 'Concurrent customer';
        $this->createDuplicateNameAfterValidation($name);

        $this->postJson(route('customers.store'), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('CUM_customers', 1);
        $this->assertDatabaseHas('CUM_customers', ['name' => $name]);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $customer = Customer::factory()->create(['name' => 'Original customer']);
        $name = 'Concurrent customer';
        $this->createDuplicateNameAfterValidation($name);

        $this->putJson(route('customers.update', $customer), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseHas('CUM_customers', ['id' => $customer->id, 'name' => 'Original customer']);
        $this->assertDatabaseHas('CUM_customers', ['name' => $name]);
        $this->assertDatabaseCount('CUM_customers', 2);
    }

    /**
     * The soft delete has to happen inside a transaction even when no rule asks for one, because a
     * guard registered by an optional integration package takes its row lock in a transaction of its
     * own and would otherwise let go of it before the delete is written.
     */
    public function test_direct_model_delete_runs_the_soft_delete_inside_a_transaction(): void
    {
        $customer = Customer::factory()->create();
        $transactionLevel = null;

        DB::listen(static function (QueryExecuted $query) use (&$transactionLevel): void {
            if (str_starts_with($query->sql, 'update') && str_contains($query->sql, 'CUM_customers')) {
                $transactionLevel = DB::transactionLevel();
            }
        });

        $initialTransactionLevel = DB::transactionLevel();

        $customer->delete();

        $this->assertSame($initialTransactionLevel + 1, $transactionLevel);
        $this->assertSoftDeleted($customer);
    }

    public function test_invalid_customer_names_are_rejected(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->postJson(route('customers.store'), ['name' => 'Abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_creation_requires_authorization(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]);
        $this->actingAs($user);
        Gate::before(static fn (): bool => false);

        $this->post(route('customers.store'), [])->assertForbidden();

        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_customer_name_with_non_latin_characters_can_be_stored_and_searched(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $name = 'Cliente Ñandú クライアント';

        $this->post(route('customers.store'), ['name' => $name])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('CUM_customers', ['name' => $name]);

        $this->get(route('customers.index', ['search' => $name]))
            ->assertOk()
            ->assertSee($name);
    }

    public function test_customer_search_is_kept_in_pagination_links(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        foreach (range(1, 30) as $index) {
            Customer::factory()->create(['name' => "Repeat customer {$index}"]);
        }

        $this->get(route('customers.index', ['search' => 'Repeat']))
            ->assertOk()
            ->assertSee('search=Repeat', false);
    }

    public function test_notes_are_saved_and_replaced_on_update(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->post(route('customers.store'), [
            'name' => 'Notes customer',
            'notes' => 'Customer notes',
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->sole();
        $this->assertSame('Customer notes', $customer->notes);

        $this->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'notes' => 'Rewritten notes',
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Rewritten notes', $customer->fresh()->notes);
    }

    public function test_notes_are_optional_and_can_be_cleared(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->post(route('customers.store'), ['name' => 'Notes customer'])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->sole();
        $this->assertNull($customer->notes);

        $this->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'notes' => 'Customer notes',
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame('Customer notes', $customer->fresh()->notes);

        $this->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'notes' => '',
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($customer->fresh()->notes);
    }

    public function test_notes_longer_than_the_configured_maximum_are_rejected(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Notes customer',
                'notes' => str_repeat('a', MaxLength::longText() + 1),
            ])->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_the_edit_form_is_bound_to_the_saved_notes(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $customer = Customer::factory()->create(['notes' => 'Customer notes']);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('x-model="form.notes"', false)
            ->assertSee('data-test="customer-notes"', false)
            ->assertViewHas('list', function (array $list) use ($customer): bool {
                return $list['rows'][0]['id'] === $customer->id
                    && $list['rows'][0]['editPayload']['notes'] === 'Customer notes';
            });
    }

    private function createDuplicateNameAfterValidation(string $name): void
    {
        $table = (new Customer)->getTable();
        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use ($name, $table, &$competitorCreated): void {
            if (
                $competitorCreated
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')
                || ! str_contains(strtolower($query->sql), strtolower($table))
                || ! in_array($name, $query->bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            Customer::factory()->create(['name' => $name]);
        });
    }
}
