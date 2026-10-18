<?php

namespace entities13\Http\Requests;

use entities13\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Entity>
 */
final class EntityRestoreRequest extends RestoreRequest
{
    /**
     * @return Entity
     */
    protected function findTrashed(): Model
    {
        return Entity::onlyTrashed()
            ->findOrFail((int) $this->route('entity'));
    }
}
