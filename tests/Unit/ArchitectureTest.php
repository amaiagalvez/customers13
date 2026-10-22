<?php

namespace Entities13\Tests\Unit;

use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Basics13\Tests\ChecksResourceArchitecture;

class ArchitectureTest extends TestCase
{
    use ChecksResourceArchitecture;

    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function resources(): array
    {
        return [
            'Entity' => Entity::class,
            'EntityType' => EntityType::class,
        ];
    }
}
