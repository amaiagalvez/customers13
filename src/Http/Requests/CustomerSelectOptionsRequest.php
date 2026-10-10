<?php

namespace Customers13\Http\Requests;

use Customers13\Models\Customer;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class CustomerSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Customer::class) ?? false;
    }
}
