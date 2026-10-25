<?php

namespace Entities13\Models;

use Carbon\CarbonImmutable;
use Entities13\Policies\PersonPolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Entities13\Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $last_name_1
 * @property string|null $last_name_2
 * @property string|null $document_number
 * @property string|null $lang
 * @property int|null $entity_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 * @property-read Person|null $entity
 * @property-read Collection<int, PersonType> $personTypes
 */
#[Fillable(['name', 'last_name_1', 'last_name_2', 'document_number', 'lang', 'entity_id', 'notes'])]
#[UsePolicy(PersonPolicy::class)]
#[UseFactory(PersonFactory::class)]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ENT_persons';

    /**
     * @var array{active: bool}
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * @return array{active: 'boolean'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * The entity this person belongs to.
     *
     * @return BelongsTo<Entity, $this>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    /**
     * The types this person is categorised with.
     *
     * @return BelongsToMany<PersonType, $this>
     */
    public function personTypes(): BelongsToMany
    {
        return $this->belongsToMany(PersonType::class, 'ENT_person_person_type', 'person_id', 'person_type_id')
            ->withTimestamps();
    }

    /**
     * Check if the person is valid (has required fields).
     */
    public function isValid(): bool
    {
        return ! empty($this->name) && ! empty($this->last_name_1);
    }

    /**
     * Compose the full name of the person.
     */
    public function getFullName(): string
    {
        $parts = [$this->name];

        if ($this->last_name_1 !== null) {
            $parts[] = $this->last_name_1;
        }

        if ($this->last_name_2 !== null) {
            $parts[] = $this->last_name_2;
        }

        return implode(' ', $parts);
    }
}
