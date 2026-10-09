<?php

namespace Customers13\Http\Requests;

use Customers13\Models\Customer;

class CustomerSelectOptionsRequest extends \Basics13\Http\Requests\SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Customer::class) ?? false;
    }
}