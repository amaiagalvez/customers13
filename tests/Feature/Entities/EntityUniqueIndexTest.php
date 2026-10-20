<?php

namespace Entities13\Tests\Feature\Entities;

use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The uniqueness of a entity name is enforced by the database itself via a partial unique index
 * on the generated `active_name` column, so a write that skipped validation still cannot
 * duplicate an active name.
 */
class EntityUniqueIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_has_the_partial_unique_index_on_active_name(): void
    {
        $indexes = collect(DB::select(
            "select name from sqlite_master where type = 'index' and tbl_name = 'ENT_entities'",
        ))->pluck('name');

        $this->assertContains('ENT_entities_active_name_unique', $indexes);
    }

    public function test_a_duplicate_active_name_hits_the_database_index(): void
    {
        Entity::factory()->create(['name' => 'Ane Bezeroa']);

        $refused = false;

        try {
            Entity::factory()->create(['name' => 'Ane Bezeroa']);
        } catch (QueryException) {
            $refused = true;
        }

        $this->assertTrue($refused, 'The database accepted a duplicate active name.');
    }

    public function test_a_name_left_by_a_trashed_entity_can_be_taken_again(): void
    {
        $trashed = Entity::factory()->trashed()->create(['name' => 'Mota zaharkitua']);

        // The same name is accepted again, because the record that held it is in the trash.
        $replacement = Entity::factory()->create(['name' => 'Mota zaharkitua']);

        $this->assertSame('Mota zaharkitua', $replacement->name);
        $this->assertSoftDeleted($trashed);
        $this->assertSame(1, Entity::query()->where('name', 'Mota zaharkitua')->count());
        $this->assertSame(1, Entity::onlyTrashed()->where('name', 'Mota zaharkitua')->count());
    }
}
