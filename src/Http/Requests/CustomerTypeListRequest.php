<?php

namespace entities13\Http\Requests;

use entities13\Models\EntityType;
use Basics13\Http\Requests\SearchableListRequest;

class EntityTypeListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', EntityType::class) ?? false;
    }
}
