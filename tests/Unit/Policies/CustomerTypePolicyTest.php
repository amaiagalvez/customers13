<?php

namespace entities13\Tests\Unit\Policies;

use App\Models\User;
use entities13\Tests\TestCase;
use entities13\Models\EntityType;
use entities13\Policies\EntityTypePolicy;

class EntityTypePolicyTest extends TestCase
{
    private EntityTypePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EntityTypePolicy;
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
        $this->assertTrue($this->policy->update(new User, $this->entityType()));
    }

    public function test_delete_returns_true(): void
    {
        $this->assertTrue($this->policy->delete(new User, $this->entityType()));
    }

    public function test_restore_returns_true(): void
    {
        $this->assertTrue($this->policy->restore(new User, $this->entityType()));
    }

    public function test_force_delete_returns_true(): void
    {
        $this->assertTrue($this->policy->forceDelete(new User, $this->entityType()));
    }

    public function test_archive_returns_true(): void
    {
        $this->assertTrue($this->policy->archive(new User, $this->entityType()));
    }

    public function test_activate_returns_true(): void
    {
        $this->assertTrue($this->policy->activate(new User, $this->entityType()));
    }

    private function entityType(): EntityType
    {
        return new EntityType(['name_eu' => 'Type under test']);
    }
}
