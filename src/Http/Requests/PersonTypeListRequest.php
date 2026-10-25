<?php

namespace Entities13\Http\Requests;

use Entities13\Models\PersonType;
use Basics13\Http\Requests\SearchableListRequest;

class PersonTypeListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PersonType::class) ?? false;
    }
}
