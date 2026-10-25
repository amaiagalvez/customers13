<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<Person>
 */
final class PersonDestroyRequest extends TrashDestroyRequest
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
