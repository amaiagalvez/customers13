<?php

namespace Customers13\Tests\Unit;

use Tests\TestCase;
use Customers13\Models\Customer;
use Customers13\Policies\CustomerPolicy;
use App\Models\User;

class CustomerPolicyTest extends TestCase
{
    public function test_policy_has_all_abilities(): void
    {
        $policy = new CustomerPolicy();

        $abilities = ['viewAny', 'create', 'update', 'delete', 'restore', 'forceDelete', 'archive', 'activate'];

        foreach ($abilities as $ability) {
            $this->assertMethodExists($policy, $ability, "CustomerPolicy must have \{$ability} method");
        }
    }

    public function test_view_any_returns_true(): void
    {
        $this->assertTrue((new ReflectionMethod(CustomerPolicy::class, 'viewAny'))->invoke(null));
    }

    public function test_create_returns_true(): void
    {
        $this->assertTrue((new ReflectionMethod(CustomerPolicy::class, 'create'))->invoke(null));
    }
}