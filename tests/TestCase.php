<?php

namespace Customers13\Tests;

use Basics13\ServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Customers13\ServiceProvider as Customers13ServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    /**
     * Loads the package so its config, translations and views are available to the tests.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
            Customers13ServiceProvider::class,
        ];
    }

    /**
     * The tests run on SQLite in memory, so nobody needs a database server to check the package.
     * The engines the application really runs are that application's own business.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }
}
