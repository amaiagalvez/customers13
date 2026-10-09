<?php

namespace Customers13\Http\Requests;

use Basics13\Http\Requests\SearchableListRequest;
use Customers13\Models\Customer;

class CustomerListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Customer::class) ?? false;
    }
}
