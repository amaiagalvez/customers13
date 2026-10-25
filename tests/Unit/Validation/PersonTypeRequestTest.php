<?php

namespace Entities13\Tests\Unit\Validation;

use Illuminate\Routing\Route;
use Entities13\Tests\TestCase;
use Illuminate\Auth\Access\Gate;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Application;
use Illuminate\Validation\Rules\Unique;
use Entities13\Policies\PersonTypePolicy;
use Basics13\Support\Validation\MaxLength;
use Basics13\Support\Testing\PermissionsActor;
use Entities13\Http\Requests\PersonTypeRequest;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * The rules of a payload are read here and run against the same payload, so the unique rule is
 * never reached: it is the one rule that needs the database, and a value that already failed a rule
 * of its own field or was left out makes the validator skip it. Uniqueness is covered where it
 * belongs, against the database and through the form.
 *
 * @property Application $app
 */
class PersonTypeRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->app->make('config');
        $config->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
        $gate = new Gate($this->app, static fn (): null => null);
        $gate->policy(PersonType::class, PersonTypePolicy::class);
        $this->app->instance(GateContract::class, $gate);
    }

    public function test_authorize_needs_the_create_permission(): void
    {
        $request = new PersonTypeRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['person_types.view']));
        $this->assertFalse($request->authorize());
    }

    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new PersonTypeRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['person_types.manage']));
        $this->assertTrue($request->authorize());
    }

    public function test_unauthenticated_users_cannot_create_person_types(): void
    {
        $this->assertFalse((new PersonTypeRequest)->authorize());
    }

    public function test_name_eu_is_required(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', ['name_eu' => '']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');
    }

    public function test_name_eu_must_be_at_least_two_characters(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', ['name_eu' => 'A']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');
    }

    public function test_name_eu_cannot_exceed_the_configured_maximum(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', [
            'name_eu' => str_repeat('a', MaxLength::string() + 1),
        ]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');
    }

    public function test_a_non_string_translation_is_rejected(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', ['name_es' => ['array']]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_es');
    }

    public function test_notes_cannot_exceed_long_text_maximum(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', [
            'notes' => str_repeat('a', MaxLength::longText() + 1),
        ]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'notes');
    }

    public function test_reuse_deleted_name_is_sometimes_boolean(): void
    {
        $request = PersonTypeRequest::create('/', 'POST', ['reuse_deleted_name' => 'not-a-boolean']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'reuse_deleted_name');
    }

    public function test_prepare_for_validation_trims_the_names(): void
    {
        $request = $this->requestWithRoute(['name_eu' => '  Pertsona mota  ', 'name_es' => '  Tipo  ']);

        $this->prepareForValidation($request);

        $this->assertSame('Pertsona mota', $request->input('name_eu'));
        $this->assertSame('Tipo', $request->input('name_es'));
    }

    public function test_prepare_for_validation_turns_a_blank_translation_into_null(): void
    {
        $request = $this->requestWithRoute(['name_eu' => 'Pertsona mota', 'name_es' => '   ']);

        $this->prepareForValidation($request);

        $this->assertNull($request->input('name_es'));
    }

    public function test_prepare_for_validation_keeps_a_blank_euskera_name_as_a_string(): void
    {
        $request = $this->requestWithRoute(['name_eu' => '  ']);

        $this->prepareForValidation($request);

        // A blank required name is left for `required` to reject, not turned into an absent one.
        $this->assertSame('', $request->input('name_eu'));
    }

    public function test_rules_return_expected_structure(): void
    {
        $rules = (new PersonTypeRequest)->rules();

        $this->assertArrayHasKey('name_eu', $rules);
        $this->assertArrayHasKey('name_es', $rules);
        $this->assertArrayHasKey('name_fr', $rules);
        $this->assertArrayHasKey('name_en', $rules);
        $this->assertArrayHasKey('notes', $rules);
        $this->assertArrayHasKey('reuse_deleted_name', $rules);
        $this->assertCount(6, $rules);
    }

    public function test_name_eu_rule_contains_required_string_min_max_and_unique(): void
    {
        $nameEu = (new PersonTypeRequest)->rules()['name_eu'];

        $this->assertContains('required', $nameEu);
        $this->assertContains('string', $nameEu);
        $this->assertContains('min:2', $nameEu);
        $this->assertContains('max:'.MaxLength::string(), $nameEu);
        $this->assertCount(1, $this->uniqueRules($nameEu));
    }

    public function test_every_language_rule_carries_its_own_unique_rule(): void
    {
        $rules = (new PersonTypeRequest)->rules();

        foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $column) {
            $this->assertCount(
                1,
                $this->uniqueRules($rules[$column]),
                "{$column} must carry exactly one unique rule.",
            );
        }
    }

    public function test_the_other_languages_are_nullable_strings(): void
    {
        $rules = (new PersonTypeRequest)->rules();

        foreach (['name_es', 'name_fr', 'name_en'] as $column) {
            $this->assertContains('nullable', $rules[$column]);
            $this->assertContains('string', $rules[$column]);
            $this->assertContains('max:'.MaxLength::string(), $rules[$column]);
        }
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, Unique>
     */
    private function uniqueRules(array $rules): array
    {
        return array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => $rule instanceof Unique,
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requestWithRoute(array $data): PersonTypeRequest
    {
        $request = PersonTypeRequest::create('/', 'POST', $data);
        $route = new Route($request->getMethod(), '/', static fn (): null => null);
        $route->bind($request);
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }

    private function prepareForValidation(PersonTypeRequest $request): void
    {
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }
}
