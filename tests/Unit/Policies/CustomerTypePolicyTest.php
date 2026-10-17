<?php

namespace Customers13\Tests\Unit\Policies;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\CustomerType;
use Customers13\Policies\CustomerTypePolicy;

class CustomerTypePolicyTest extends TestCase
{
    private CustomerTypePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CustomerTypePolicy;
    }

    public function test_view_any_returns_true(): void
    {
        $this->assertTrue($this->policy->viewAny(new User));
    }

    public function test_create_returns_true(): void
    {
        $this->assertTrue($this->policy->create(new User));
    }

    public function test_update_returns_true(): void
    {
        $this->assertTrue($this->policy->update(new User, $this->customerType()));
    }

    public function test_delete_returns_true(): void
    {
        $this->assertTrue($this->policy->delete(new User, $this->customerType()));
    }

    public function test_restore_returns_true(): void
    {
        $this->assertTrue($this->policy->restore(new User, $this->customerType()));
    }

    public function test_force_delete_returns_true(): void
    {
        $this->assertTrue($this->policy->forceDelete(new User, $this->customerType()));
    }

    public function test_archive_returns_true(): void
    {
        $this->assertTrue($this->policy->archive(new User, $this->customerType()));
    }

    public function test_activate_returns_true(): void
    {
        $this->assertTrue($this->policy->activate(new User, $this->customerType()));
    }

    private function customerType(): CustomerType
    {
        return new CustomerType(['name_eu' => 'Type under test']);
    }
}
