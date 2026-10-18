<?php

namespace entities13\Http\Requests;

use entities13\Models\Entity;
use Basics13\Http\Requests\SearchableListRequest;

class EntityListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Entity::class) ?? false;
    }
}
