<?php

namespace entities13\Tests\Unit\Validation;

use App\Models\User;
use Illuminate\Routing\Route;
use entities13\Tests\TestCase;
use entities13\Models\Entity;
use Illuminate\Auth\Access\Gate;
use Illuminate\Foundation\Application;
use Illuminate\Validation\Rules\Unique;
use entities13\Policies\EntityPolicy;
use Illuminate\Support\Facades\Validator;
use Basics13\Support\Validation\MaxLength;
use entities13\Http\Requests\EntityRequest;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * @property Application $app
 */
class EntityRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->app->make('config');
        $config->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
        $gate = new Gate($this->app, static fn(): null => null);
        $gate->policy(Entity::class, EntityPolicy::class);
        $this->app->instance(GateContract::class, $gate);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new EntityRequest;
        $request->setUserResolver(static fn(): User => new User);
        $this->assertTrue($request->authorize());
    }

    public function test_authorize_returns_true_when_updating(): void
    {
        $entity = new Entity(['name' => 'Test Entity']);
        $request = EntityRequest::create('/', 'POST');
        $route = new Route($request->getMethod(), '/', static fn(): null => null);
        $route->bind($request);
        $request->setRouteResolver(static fn(): Route => $route);
        $request->setUserResolver(static fn(): User => new User);

        $this->assertTrue($request->authorize());
    }

    public function test_unauthenticated_users_cannot_create_entities(): void
    {
        $request = new EntityRequest;
        $this->assertFalse($request->authorize());
    }

    public function test_name_is_required(): void
    {
        $request = EntityRequest::create('/', 'POST', ['name' => '']);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'name');
    }

    public function test_name_must_be_a_string(): void
    {
        $request = EntityRequest::create('/', 'POST', ['name' => ['Ane Bezeroa']]);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'name');
    }

    public function test_name_cannot_exceed_the_configured_maximum(): void
    {
        $name = str_repeat('a', MaxLength::string() + 1);
        $request = EntityRequest::create('/', 'POST', ['name' => $name]);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'name');
    }

    public function test_name_must_be_at_least_four_characters(): void
    {
        $request = EntityRequest::create('/', 'POST', ['name' => 'Abc']);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'name');
    }

    public function test_notes_is_nullable_string_with_max_length(): void
    {
        $request = EntityRequest::create('/', 'POST', ['notes' => 123]);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'notes');
    }

    public function test_notes_cannot_exceed_long_text_maximum(): void
    {
        $notes = str_repeat('a', MaxLength::longText() + 1);
        $request = EntityRequest::create('/', 'POST', ['notes' => $notes]);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'notes');
    }

    public function test_reuse_deleted_name_is_sometimes_boolean(): void
    {
        $request = EntityRequest::create('/', 'POST', ['reuse_deleted_name' => 'not-a-boolean']);
        $this->assertRequestValidationFails($request, static fn(): array => $request->rules(), 'reuse_deleted_name');
    }

    public function test_prepare_for_validation_trims_name(): void
    {
        $request = EntityRequest::create('/', 'POST', ['name' => '  Trimmed Name  ']);
        $route = new Route($request->getMethod(), '/', static fn(): null => null);
        $route->bind($request);
        $request->setRouteResolver(static fn(): Route => $route);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertSame('Trimmed Name', $request->input('name'));
    }

    public function test_prepare_for_validation_does_not_trim_non_string_name(): void
    {
        $request = EntityRequest::create('/', 'POST', ['name' => ['array']]);
        $route = new Route($request->getMethod(), '/', static fn(): null => null);
        $route->bind($request);
        $request->setRouteResolver(static fn(): Route => $route);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertSame(['array'], $request->input('name'));
    }

    public function test_rules_return_expected_structure(): void
    {
        $request = new EntityRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('notes', $rules);
        $this->assertArrayHasKey('entity_type_ids', $rules);
        $this->assertArrayHasKey('entity_type_ids.*', $rules);
        $this->assertArrayHasKey('reuse_deleted_name', $rules);
        $this->assertCount(5, $rules);
    }

    public function test_entity_type_ids_rule_is_optional_and_an_array(): void
    {
        $request = new EntityRequest;
        $rules = $request->rules();

        $this->assertContains('sometimes', $rules['entity_type_ids']);
        $this->assertContains('array', $rules['entity_type_ids']);
    }

    public function test_name_rule_contains_required_string_min_max_unique(): void
    {
        $request = new EntityRequest;
        $rules = $request->rules();

        $nameRules = $rules['name'];
        $this->assertContains('required', $nameRules);
        $this->assertContains('string', $nameRules);
        $this->assertContains('min:4', $nameRules);
        $this->assertContains('max:' . MaxLength::string(), $nameRules);
    }

    public function test_name_rule_contains_unique_scoped_to_active(): void
    {
        $request = new EntityRequest;
        $rules = $request->rules();

        $nameRules = $rules['name'];
        $uniqueRules = array_filter($nameRules, static fn(mixed $rule): bool => $rule instanceof Unique);
        $this->assertNotEmpty($uniqueRules, 'name rule must contain unique rule');
    }

    public function test_entity_attribute_is_translated_by_the_resource_request(): void
    {
        $this->app->setLocale('eu');
        $request = new EntityRequest;
        $validator = Validator::make([], ['name' => ['required']], [], $request->attributes());

        $this->assertSame(
            __('validation.required', ['attribute' => __('validation.attributes.name')]),
            $validator->errors()->first('name'),
        );
    }
}
