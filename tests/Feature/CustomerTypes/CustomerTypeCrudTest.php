<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
use Illuminate\Support\Facades\Gate;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customer-types.index'))->assertRedirect(route('login'));
        $this->post(route('customer-types.store'), ['name_eu' => 'Guest type'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('CUM_customer_types', 0);
    }

    public function test_unverified_users_cannot_create_customer_types(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused']);

        $this->actingAs($user)
            ->post(route('customer-types.store'), ['name_eu' => 'Unverified type'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('CUM_customer_types', 0);
    }

    public function test_empty_customer_type_list_shows_its_empty_state(): void
    {
        $this->actingAs($this->user())
            ->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee('No records yet.');
    }

    public function test_authenticated_users_can_create_update_and_delete_customer_types(): void
    {
        $this->actingAs($this->user());

        $this->post(route('customer-types.store'), [
            'name_eu' => 'Bezero mota',
            'name_es' => 'Tipo de cliente',
            'name_fr' => 'Type de client',
            'name_en' => 'Customer type',
        ])
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $customerType = CustomerType::query()->sole();
        $this->assertSame('Bezero mota', $customerType->name_eu);
        $this->assertSame('Customer type', $customerType->name_en);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee('customer-type-create')
            ->assertSee('aria-labelledby="customer-type-form-heading"', false)
            ->assertSee('id="customer-type-form-heading"', false)
            ->assertSee('customer-type-edit-'.$customerType->id)
            ->assertSee('Bezero mota')
            ->assertSee('Tipo de cliente')
            ->assertSee('Type de client')
            ->assertSee('Customer type');

        $this->put(route('customer-types.update', $customerType), [
            'name_eu' => 'Bezero mota berria',
            'name_es' => 'Tipo de cliente nuevo',
        ])
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('CUM_customer_types', [
            'id' => $customerType->id,
            'name_eu' => 'Bezero mota berria',
            'name_es' => 'Tipo de cliente nuevo',
        ]);

        $this->delete(route('customer-types.destroy', $customerType))
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($customerType);
    }

    public function test_the_list_shows_the_four_language_columns(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->create([
            'name_eu' => 'Mota euskaraz',
            'name_es' => 'Tipo en castellano',
            'name_fr' => 'Type en français',
            'name_en' => 'Type in English',
        ]);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee(__('Name (eu)'))
            ->assertSee(__('Name (es)'))
            ->assertSee(__('Name (fr)'))
            ->assertSee(__('Name (en)'))
            ->assertSee('Mota euskaraz')
            ->assertSee('Tipo en castellano')
            ->assertSee('Type en français')
            ->assertSee('Type in English');
    }

    public function test_each_language_name_is_unique_among_the_types_outside_the_trash(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->create(['name_es' => 'Duplicado']);

        $this->from(route('customer-types.index'))
            ->post(route('customer-types.store'), [
                'name_eu' => 'Mota berria',
                'name_es' => 'Duplicado',
            ])
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHasErrors('name_es');

        $this->assertSame(1, CustomerType::query()->where('name_es', 'Duplicado')->count());
    }

    public function test_name_eu_is_required_and_at_least_two_characters(): void
    {
        $this->actingAs($this->user());

        $this->from(route('customer-types.index'))
            ->post(route('customer-types.store'), ['name_es' => 'Solo castellano'])
            ->assertSessionHasErrors('name_eu');

        $this->from(route('customer-types.index'))
            ->post(route('customer-types.store'), ['name_eu' => 'A'])
            ->assertSessionHasErrors('name_eu');

        $this->assertDatabaseCount('CUM_customer_types', 0);
    }

    public function test_the_other_languages_are_optional_and_blank_ones_are_stored_as_null(): void
    {
        $this->actingAs($this->user());

        $this->post(route('customer-types.store'), [
            'name_eu' => 'Mota',
            'name_es' => '',
            'name_fr' => '   ',
        ])
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHasNoErrors();

        $customerType = CustomerType::query()->sole();
        $this->assertNull($customerType->name_es);
        $this->assertNull($customerType->name_fr);
        $this->assertNull($customerType->name_en);
    }

    public function test_json_creation_trims_the_names_and_returns_the_created_record(): void
    {
        $this->actingAs($this->user());

        $this->postJson(route('customer-types.store'), [
            'name_eu' => '  Bezero mota  ',
            'name_es' => '  Tipo de cliente  ',
        ])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name_eu'])
            ->assertJsonPath('name_eu', 'Bezero mota');

        $this->assertDatabaseHas('CUM_customer_types', [
            'name_eu' => 'Bezero mota',
            'name_es' => 'Tipo de cliente',
        ]);
    }

    public function test_html_creation_opens_the_restore_conflict_for_a_trashed_name(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => 'Tipo en la papelera',
        ]);

        $this->post(route('customer-types.store'), ['name_eu' => 'Mota zaharkitua'])
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('deleted_customer_type_conflict', [
                'id' => $customerType->id,
                'name_eu' => 'Mota zaharkitua',
                'name_es' => 'Tipo en la papelera',
                'name_fr' => $customerType->name_fr,
                'name_en' => $customerType->name_en,
            ]);

        $this->assertSame(0, CustomerType::query()->where('name_eu', 'Mota zaharkitua')->count());
    }

    public function test_json_creation_requires_explicit_reuse_of_a_trashed_name(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create(['name_eu' => 'Mota zaharkitua']);

        $this->postJson(route('customer-types.store'), ['name_eu' => $customerType->name_eu])
            ->assertConflict()
            ->assertJsonPath('errors.name_eu.0', __('basics13::messages.deleted_name_conflict', [
                'name' => $customerType->name_eu,
            ]));

        $this->postJson(route('customer-types.store'), [
            'name_eu' => $customerType->name_eu,
            'reuse_deleted_name' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('name_eu', $customerType->name_eu);

        $this->assertSoftDeleted($customerType);
        $this->assertSame(1, CustomerType::query()->where('name_eu', $customerType->name_eu)->count());
    }

    public function test_a_type_a_customer_holds_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();
        Customer::factory()->create()->customerTypes()->attach($customerType);

        $this->delete(route('customer-types.destroy', $customerType))
            ->assertRedirect(route('customer-types.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($customerType);
        $this->assertDatabaseHas('CUM_customer_customer_type', ['customer_type_id' => $customerType->id]);
    }

    public function test_a_type_only_a_trashed_customer_holds_still_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create();
        $customer = Customer::factory()->create();
        $customer->customerTypes()->attach($customerType);
        $customer->delete();

        $this->assertSame(1, DB::table('CUM_customer_customer_type')
            ->where('customer_type_id', $customerType->id)->count());

        $this->delete(route('customer-types.destroy', $customerType))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($customerType);
    }

    public function test_a_type_with_customers_offers_archive_instead_of_delete(): void
    {
        $this->actingAs($this->user());
        $held = CustomerType::factory()->create();
        Customer::factory()->create()->customerTypes()->attach($held);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee('data-test="customer-type-archive-'.$held->id.'"', false)
            ->assertDontSee('data-test="customer-type-delete-'.$held->id.'"', false)
            ->assertSee(__('basics13::messages.archive_instead_of_delete'));
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->user());
        $name = 'Mota konkurrentea';
        $this->createDuplicateNameAfterValidation($name);

        $this->postJson(route('customer-types.store'), ['name_eu' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');

        $this->assertDatabaseCount('CUM_customer_types', 1);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create(['name_eu' => 'Mota originala']);
        $name = 'Mota konkurrentea';
        $this->createDuplicateNameAfterValidation($name);

        $this->putJson(route('customer-types.update', $customerType), ['name_eu' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');

        $this->assertDatabaseHas('CUM_customer_types', [
            'id' => $customerType->id,
            'name_eu' => 'Mota originala',
        ]);
        $this->assertDatabaseCount('CUM_customer_types', 2);
    }

    public function test_creation_requires_authorization(): void
    {
        $this->actingAs($this->user());
        Gate::before(static fn (): bool => false);

        $this->post(route('customer-types.store'), ['name_eu' => 'Mota'])->assertForbidden();

        $this->assertDatabaseCount('CUM_customer_types', 0);
    }

    public function test_notes_longer_than_the_configured_maximum_are_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('customer-types.index'))
            ->post(route('customer-types.store'), [
                'name_eu' => 'Mota',
                'notes' => str_repeat('a', MaxLength::longText() + 1),
            ])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('CUM_customer_types', 0);
    }

    public function test_the_edit_form_is_bound_to_the_saved_names(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->create([
            'name_eu' => 'Mota',
            'name_es' => 'Tipo',
            'name_fr' => 'Type',
            'name_en' => 'Kind',
            'notes' => 'Type notes',
        ]);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee('x-model="form.name_eu"', false)
            ->assertSee('data-test="customer-type-name_eu"', false)
            ->assertViewHas('list', function (array $list) use ($customerType): bool {
                $payload = $list['rows'][0]['editPayload'];

                return $list['rows'][0]['id'] === $customerType->id
                    && $payload['name_eu'] === 'Mota'
                    && $payload['name_es'] === 'Tipo'
                    && $payload['name_fr'] === 'Type'
                    && $payload['name_en'] === 'Kind'
                    && $payload['notes'] === 'Type notes';
            });
    }

    /**
     * A write that lost the race to a concurrent one is refused by the database, and the request
     * turns that refusal into the validation error a form shows on the field.
     */
    private function createDuplicateNameAfterValidation(string $name): void
    {
        $table = (new CustomerType)->getTable();
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
            CustomerType::factory()->create(['name_eu' => $name]);
        });
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
