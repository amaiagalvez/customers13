<?php

namespace entities13\Http\Requests;

use entities13\Models\Entity;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class EntitySelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Entity::class) ?? false;
    }
}
