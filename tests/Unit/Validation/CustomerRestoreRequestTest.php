<?php

namespace entities13\Tests\Unit\Validation;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use entities13\Http\Requests\EntityRestoreRequest;

class EntityRestoreRequestTest extends TestCase
{
    public function test_find_trashed_returns_entity(): void
    {
        $method = new ReflectionMethod(EntityRestoreRequest::class, 'findTrashed');
        $this->assertTrue($method->isProtected());
        $this->assertSame(EntityRestoreRequest::class, $method->getDeclaringClass()->getName());
    }
}
