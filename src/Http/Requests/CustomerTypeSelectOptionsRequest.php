<?php

namespace Customers13\Http\Requests;

use Customers13\Models\CustomerType;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class CustomerTypeSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CustomerType::class) ?? false;
    }
}
