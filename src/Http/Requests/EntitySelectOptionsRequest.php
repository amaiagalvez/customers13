<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Entity;
use Entities13\Contracts\EntityIntegration;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class EntitySelectOptionsRequest extends SearchableSelectOptionsRequest
{
    /**
     * Whoever lists entities may pick them, and so may whoever the integration lets choose one in
     * a form of its own.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->can('viewAny', Entity::class)
            || app(EntityIntegration::class)->allowsSelectOptions($user)
        );
    }
}
