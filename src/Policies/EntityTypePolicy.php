<?php

namespace Entities13\Policies;

use Entities13\Models\EntityType;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Entity type abilities, each one granted by its `entity_types.{action}` permission.
 */
class EntityTypePolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'entity_types.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->permits($user, 'entity_types.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.force_delete');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.archive');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, EntityType $entityType): bool
    {
        return $this->permits($user, 'entity_types.archive');
    }
}
