<?php

namespace Entities13\Tests;

use App\Models\User;
use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Basics13\Tests\TestCase as Basics13TestCase;
use Basics13\Support\Testing\ValidatesFormRequests;
use Entities13\ServiceProvider as entities13ServiceProvider;

abstract class TestCase extends Basics13TestCase
{
    use ValidatesFormRequests;

    /**
     * Loads the package so its config, translations and views are available to the tests.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FluxServiceProvider::class,
            entities13ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // SQLite by default; CI sets DB_CONNECTION=mysql to run the tests that need MariaDB's row
        // locks, the same override the bridge suite has.
        $app['config']->set('database.default', getenv('DB_CONNECTION') ?: 'sqlite');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('view.compiled', dirname(__DIR__).'/storage/framework/views');
    }

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        Route::get('projects', static fn (): string => 'Projects')->name('projects.index');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(__DIR__.'/Fixtures/views/layouts', 'layouts');
    }
}
