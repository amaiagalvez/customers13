<?php

namespace Customers13\Http\Requests;

use Customers13\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends Basics13\Http\Requests\RestoreRequest<Customer>
 */
final class CustomerRestoreRequest extends RestoreRequest
{
    /**
     * @return Customer
     */
    protected function findTrashed(): Model
    {
        return Customer::onlyTrashed()
            ->findOrFail((int) $this->route('customer'));
    }
}
