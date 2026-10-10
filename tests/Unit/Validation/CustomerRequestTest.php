<?php

namespace Customers13\Tests\Unit\Validation;

use App\Models\User;
use PHPUnit\Framework\TestCase;
use Customers13\Models\Customer;
use Illuminate\Auth\Access\Gate;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Customers13\Policies\CustomerPolicy;
use Customers13\Http\Requests\CustomerRequest;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class CustomerRequestTest extends TestCase
{
    private Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $container = new Container;
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'basics13' => ['validation' => ['max_length' => ['string' => 123, 'longtext' => 456]]],
        ]));
        $gate = new Gate($container, static fn (): null => null);
        $gate->policy(Customer::class, CustomerPolicy::class);
        $container->instance(GateContract::class, $gate);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new CustomerRequest;
        $request->setUserResolver(static fn (): User => new User);
        $this->assertTrue($request->authorize());
    }

    public function test_unauthenticated_users_cannot_create_customers(): void
    {
        $request = new CustomerRequest;
        $this->assertFalse($request->authorize());
    }

    public function test_name_rules_include_required_and_configured_length_limits(): void
    {
        $request = new CustomerRequest;
        $rules = $request->rules()['name'];
        $this->assertContains('required', $rules);
        $this->assertContains('min:4', $rules);
        $this->assertContains('max:123', $rules);
    }
}
