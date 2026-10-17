<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use Customers13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CustomerTypeMigrationTest extends TestCase
{
    public function test_customer_type_migrations_can_be_rolled_back(): void
    {
        $connectionName = 'customers13_customer_type_migration_test';
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

            $customers = require $migrations.'/2026_09_25_000000_create_cum_customers_table.php';
            $customerTypes = require $migrations.'/2026_10_16_000000_create_cum_customer_types_table.php';
            $pivot = require $migrations.'/2026_10_16_000001_create_cum_customer_customer_type_table.php';

            $customers->up();
            $customerTypes->up();
            $pivot->up();

            $this->assertTrue(Schema::hasTable('CUM_customers'));
            $this->assertTrue(Schema::hasTable('CUM_customer_types'));
            $this->assertTrue(Schema::hasTable('CUM_customer_customer_type'));

            $pivot->down();
            $customerTypes->down();
            $customers->down();

            $this->assertFalse(Schema::hasTable('CUM_customer_customer_type'));
            $this->assertFalse(Schema::hasTable('CUM_customer_types'));
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
