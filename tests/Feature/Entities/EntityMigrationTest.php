<?php

namespace entities13\Tests\Feature\Entities;

use entities13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class EntityMigrationTest extends TestCase
{
    public function test_entity_migration_can_be_rolled_back(): void
    {
        $connectionName = 'entities13_migration_test';
        $originalDefault = config('database.default');
        $sqliteConnection = config('database.connections.sqlite');

        if (! is_array($sqliteConnection)) {
            throw new \RuntimeException('The SQLite test connection is not configured.');
        }

        config([
            "database.connections.{$connectionName}" => array_merge($sqliteConnection, [
                'database' => ':memory:',
            ]),
            'database.default' => $connectionName,
        ]);

        DB::purge($connectionName);

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $migration = require dirname(__DIR__, 3).'/database/migrations/2026_09_25_000000_create_ent_entities_table.php';
            $migration->up();

            $this->assertTrue(Schema::hasTable('ENT_entities'));

            $migration->down();

            $this->assertFalse(Schema::hasTable('ENT_entities'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
