<?php

namespace Customers13\Tests\Feature\Customers;

use Customers13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CustomerMigrationTest extends TestCase
{
    public function test_customer_migration_can_be_rolled_back(): void
    {
        $connectionName = 'customers13_migration_test';
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

            $migration = require dirname(__DIR__, 3).'/src/Database/Migrations/2026_09_25_000000_create_cum_customers_table.php';
            $migration->up();

            $this->assertTrue(Schema::hasTable('CUM_customers'));

            $migration->down();

            $this->assertFalse(Schema::hasTable('CUM_customers'));
        } finally {
            DB::purge($connectionName);
            config([
                'database.default' => $originalDefault,
                "database.connections.{$connectionName}" => null,
            ]);
        }
    }
}
