<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $email
 */
#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * @return array{email_verified_at: 'datetime'}
     */
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime'];
    }
}
