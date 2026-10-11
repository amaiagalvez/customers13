<?php

namespace Customers13\Tests;

use App\Models\User;
use Basics13\ServiceProvider;
use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\LivewireServiceProvider;
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
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
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
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('view.compiled', dirname(__DIR__).'/storage/framework/views');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }

    protected function defineRoutes($router): void
    {
        Route::get('login', static fn (): string => 'Login')->name('login');
        Route::get('email/verify', static fn (): string => 'Verify email')->name('verification.notice');
        Route::get('projects', static fn (): string => 'Projects')->name('projects.index');
        Route::get('dashboard', static fn (): string => 'Dashboard')->name('dashboard');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(__DIR__.'/Fixtures/views/layouts', 'layouts');
    }
}
