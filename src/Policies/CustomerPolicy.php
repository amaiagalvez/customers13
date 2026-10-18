<?php

namespace entities13\Policies;

use entities13\Models\Entity;
use Illuminate\Contracts\Auth\Authenticatable as User;

class EntityPolicy
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
    public function update(User $user, Entity $entity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Entity $entity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Entity $entity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Entity $entity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Entity $entity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, Entity $entity): bool
    {
        return true;
    }
}
