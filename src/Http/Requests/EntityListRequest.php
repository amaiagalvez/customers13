<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Entity;
use Basics13\Http\Requests\SearchableListRequest;

class EntityListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Entity::class) ?? false;
    }
}
