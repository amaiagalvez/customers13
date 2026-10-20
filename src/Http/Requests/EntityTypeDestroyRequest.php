<?php

namespace Entities13\Http\Requests;

use Entities13\Models\EntityType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<EntityType>
 */
final class EntityTypeDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return EntityType
     */
    protected function findTrashed(): Model
    {
        return EntityType::onlyTrashed()
            ->findOrFail((int) $this->route('entity_type'));
    }
}
