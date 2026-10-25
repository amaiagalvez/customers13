<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Person>
 */
final class PersonRestoreRequest extends RestoreRequest
{
    /**
     * @return Person
     */
    protected function findTrashed(): Model
    {
        return Person::onlyTrashed()
            ->findOrFail((int) $this->route('person'));
    }
}
