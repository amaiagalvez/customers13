<?php

namespace Customers13\Tests\Unit\Validation;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Customers13\Http\Requests\CustomerRestoreRequest;

class CustomerRestoreRequestTest extends TestCase
{
    public function test_find_trashed_returns_customer(): void
    {
        $method = new ReflectionMethod(CustomerRestoreRequest::class, 'findTrashed');
        $this->assertTrue($method->isProtected());
        $this->assertSame(CustomerRestoreRequest::class, $method->getDeclaringClass()->getName());
    }
}
