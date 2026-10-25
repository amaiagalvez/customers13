<?php

namespace Entities13\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class DuskTestCase extends \Basics13\Support\Testing\DuskTestCase
{
    /** @var class-string */
    protected string $seeder = AccessSeeder::class;

    /**
     * Create a user with verified email for Dusk tests.
     */
    protected function user(array $attributes = []): Model&Authenticatable
    {
        return parent::user(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }
}
