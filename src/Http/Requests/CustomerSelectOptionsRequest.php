<?php

namespace Customers13\Http\Requests;

use Basics13\Http\Requests\SearchableSelectOptionsRequest;
use Customers13\Models\Customer;

class CustomerSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Customer::class) ?? false;
    }
}
