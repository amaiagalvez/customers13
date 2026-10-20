<?php

namespace Entities13\Http\Requests;

use Entities13\Models\EntityType;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class EntityTypeSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', EntityType::class) ?? false;
    }
}
