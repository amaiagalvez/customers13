<?php

namespace Customers13\Policies;

use Customers13\Models\CustomerType;
use Illuminate\Contracts\Auth\Authenticatable as User;

class CustomerTypePolicy
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
    public function update(User $user, CustomerType $customerType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerType $customerType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomerType $customerType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomerType $customerType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, CustomerType $customerType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, CustomerType $customerType): bool
    {
        return true;
    }
}
