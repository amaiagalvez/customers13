<?php

namespace Customers13\Http\Requests;

use Customers13\Models\CustomerType;
use Basics13\Http\Requests\SearchableListRequest;

class CustomerTypeListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CustomerType::class) ?? false;
    }
}
