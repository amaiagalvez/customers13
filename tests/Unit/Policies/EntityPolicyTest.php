<?php

namespace entities13\Tests\Unit\Policies;

use App\Models\User;
use entities13\Models\Entity;
use entities13\Tests\TestCase;
use entities13\Policies\EntityPolicy;

class EntityPolicyTest extends TestCase
{
    private EntityPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EntityPolicy;
    }

    public function test_view_any_returns_true(): void
    {
        $user = new User;

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_create_returns_true(): void
    {
        $user = new User;

        $this->assertTrue($this->policy->create($user));
    }

    public function test_update_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->update($user, $entity));
    }

    public function test_delete_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->delete($user, $entity));
    }

    public function test_restore_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->restore($user, $entity));
    }

    public function test_force_delete_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->forceDelete($user, $entity));
    }

    public function test_archive_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->archive($user, $entity));
    }

    public function test_activate_returns_true(): void
    {
        $user = new User;
        $entity = new Entity(['name' => 'Entity under test']);

        $this->assertTrue($this->policy->activate($user, $entity));
    }
}
