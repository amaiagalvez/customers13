<?php

namespace Customers13\Tests\Unit\Policies;

use Tests\TestCase;
use ReflectionMethod;
use Customers13\Policies\CustomerPolicy;

class CustomerPolicyTest extends TestCase
{
    public function test_policy_has_all_abilities(): void
    {
        $abilities = ['viewAny', 'create', 'update', 'delete', 'restore', 'forceDelete', 'archive', 'activate'];

        foreach ($abilities as $ability) {
            $method = new ReflectionMethod(CustomerPolicy::class, $ability);
            $this->assertTrue($method->isPublic(), "CustomerPolicy::{$ability} must be public");
        }
    }

    public function test_view_any_is_public(): void
    {
        $method = new ReflectionMethod(CustomerPolicy::class, 'viewAny');
        $this->assertTrue($method->isPublic());
    }

    public function test_create_is_public(): void
    {
        $method = new ReflectionMethod(CustomerPolicy::class, 'create');
        $this->assertTrue($method->isPublic());
    }
}
