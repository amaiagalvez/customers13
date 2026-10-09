<?php

namespace Customers13\Http\Requests;

use Customers13\Models\Customer;

class CustomerListRequest extends \Basics13\Http\Requests\SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Customer::class) ?? false;
    }
}