<?php

namespace entities13\Tests\Feature\Entities;

use App\Models\User;
use entities13\Tests\TestCase;
use entities13\Models\Entity;
use entities13\Contracts\EntityIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use entities13\Tests\Fixtures\Contracts\EntityWithChildrenIntegration;

/**
 * What hangs from a entity is the business of whichever package knows about it, so this package
 * only reads the one row key the integration reports and turns the delete action into an archive
 * one. The stand-in integration exercises that contract on its own terms; the integration package
 * exercises it with real records.
 */
class EntityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_row_that_has_something_hanging_from_it_offers_archive_instead_of_delete(): void
    {
        $this->app->instance(EntityIntegration::class, new EntityWithChildrenIntegration);

        $entity = Entity::factory()->create();

        $response = $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]))->get(route('entities.index'));

        $response->assertOk();
        $response->assertSee('data-test="entity-archive-' . $entity->id . '"', false);
        $response->assertDontSee('data-test="entity-delete-' . $entity->id . '"', false);
        $response->assertSee(__('basics13::messages.archive_instead_of_delete'));
    }
}
