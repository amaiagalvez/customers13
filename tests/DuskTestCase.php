<?php

namespace Customers13\Tests;

use App\Models\User;

abstract class DuskTestCase extends \Basics13\Support\Testing\DuskTestCase
{
    /**
     * Create a user with verified email for Dusk tests.
     */
    protected function user(array $attributes = []): User
    {
        $user = parent::user(array_merge([
            'email_verified_at' => now(),
        ], $attributes));

        static::assertInstanceOf(User::class, $user);

        return $user;
    }
}
