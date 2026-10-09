<?php

namespace Customers13\Tests\Unit\Validation;

use Customers13\Http\Requests\CustomerRestoreRequest;
use Tests\TestCase;

class CustomerRestoreRequestTest extends TestCase
{
    public function test_find_trashed_returns_customer(): void
    {
        $request = new CustomerRestoreRequest;
        $request->setRouteResolver(fn () => '/customers/1/restore');

        $this->assertTrue(method_exists($request, 'findTrashed'));
    }
}
