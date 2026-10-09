<?php

namespace Customers13\Http\Requests;

use Basics13\Http\Requests\TrashDestroyRequest;
use Customers13\Models\Customer;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Basics13\Http\Requests\TrashDestroyRequest<Customer>
 */
final class CustomerDestroyRequest extends TrashDestroyRequest
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
