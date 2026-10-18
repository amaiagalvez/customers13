<?php

namespace entities13\Policies;

use entities13\Models\EntityType;
use Illuminate\Contracts\Auth\Authenticatable as User;

class EntityTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EntityType $entityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EntityType $entityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EntityType $entityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EntityType $entityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, EntityType $entityType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, EntityType $entityType): bool
    {
        return true;
    }
}
