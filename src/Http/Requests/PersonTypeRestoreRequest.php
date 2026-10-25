<?php

namespace Entities13\Http\Requests;

use Entities13\Models\PersonType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<PersonType>
 */
final class PersonTypeRestoreRequest extends RestoreRequest
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
