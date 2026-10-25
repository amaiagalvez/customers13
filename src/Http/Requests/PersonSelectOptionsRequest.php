<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Person;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class PersonSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Person::class) ?? false;
    }
}
