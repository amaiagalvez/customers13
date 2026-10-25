<?php

namespace Entities13\Http\Requests;

use Entities13\Models\PersonType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<PersonType>
 */
final class PersonTypeDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return PersonType
     */
    protected function findTrashed(): Model
    {
        return PersonType::onlyTrashed()
            ->findOrFail((int) $this->route('person_type'));
    }
}
