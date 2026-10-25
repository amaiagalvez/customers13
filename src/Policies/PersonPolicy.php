<?php

namespace Entities13\Policies;

use Entities13\Models\Person;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Person abilities, each one granted by its `persons.{action}` permission.
 */
class PersonPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'persons.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->permits($user, 'persons.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.force_delete');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.archive');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, Person $person): bool
    {
        return $this->permits($user, 'persons.archive');
    }
}
