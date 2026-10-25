<?php

namespace Entities13\Tests;

use App\Models\User;
use Basics13\Models\Role;
use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Blade;
use Basics13\Models\User as BasicsUser;
use Maatwebsite\Excel\ExcelServiceProvider;
use Basics13\Tests\TestCase as Basics13TestCase;
use Basics13\Support\Testing\ValidatesFormRequests;
use Entities13\ServiceProvider as entities13ServiceProvider;

abstract class TestCase extends Basics13TestCase
{
    use ValidatesFormRequests;

    /** @var class-string */
    protected string $seeder = AccessSeeder::class;

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
            ExcelServiceProvider::class,
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

    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(__DIR__.'/Fixtures/views/layouts', 'layouts');
    }

    /**
     * Create a user with the `admin` system role, which the access seeders grant full access
     * to entities and entity types.
     */
    protected function createAdminUser(): BasicsUser
    {
        return BasicsUser::factory()->admin()->create();
    }

    /**
     * Create a user whose only role holds exactly these permissions.
     *
     * @param  list<string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions = []): BasicsUser
    {
        return BasicsUser::factory()->withPermissions($permissions)->create();
    }

    /**
     * Give the account the `admin` system role, which the access seeders grant full access
     * to entities and entity types.
     */
    protected function admin(User $user): User
    {
        $user->assignRole(Role::query()->where('slug', 'admin')->firstOrFail());

        return $user;
    }
}
