<?php

namespace Customers13\Http\Requests;

use Customers13\Models\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<CustomerType>
 */
final class CustomerTypeDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return CustomerType
     */
    protected function findTrashed(): Model
    {
        return CustomerType::onlyTrashed()
            ->findOrFail((int) $this->route('customer_type'));
    }
}
