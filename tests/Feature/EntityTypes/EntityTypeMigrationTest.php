<?php

namespace entities13\Tests\Feature\EntityTypes;

use entities13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class EntityTypeMigrationTest extends TestCase
{
    public function test_entity_type_migrations_can_be_rolled_back(): void
    {
        $connectionName = 'entities13_entity_type_migration_test';
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

        $migrations = dirname(__DIR__, 3).'/database/migrations';

        try {
            Schema::create('users', static function (Blueprint $table): void {
                $table->id();
            });

            $entities = require $migrations.'/2026_09_25_000000_create_ent_entities_table.php';
            $entityTypes = require $migrations.'/2026_10_16_000000_create_ent_entity_types_table.php';
            $pivot = require $migrations.'/2026_10_16_000001_create_ent_entity_entity_type_table.php';

            $entities->up();
            $entityTypes->up();
            $pivot->up();

            $this->assertTrue(Schema::hasTable('ENT_entities'));
            $this->assertTrue(Schema::hasTable('ENT_entity_types'));
            $this->assertTrue(Schema::hasTable('ENT_entity_entity_type'));

            $pivot->down();
            $entityTypes->down();
            $entities->down();

            $this->assertFalse(Schema::hasTable('ENT_entity_entity_type'));
            $this->assertFalse(Schema::hasTable('ENT_entity_types'));
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
