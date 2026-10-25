<?php

namespace Entities13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Entities13\Policies\PersonTypePolicy;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Entities13\Database\Factories\PersonTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name_eu
 * @property string|null $name_es
 * @property string|null $name_fr
 * @property string|null $name_en
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 */
#[Fillable(['name_eu', 'name_es', 'name_fr', 'name_en', 'notes'])]
#[UsePolicy(PersonTypePolicy::class)]
#[UseFactory(PersonTypeFactory::class)]
class PersonType extends Model
{
    /** @use HasFactory<PersonTypeFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ENT_person_types';

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
     * Language columns for the type name.
     *
     * @var list<string>
     */
    public const NAME_FIELDS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    /**
     * The persons holding this type.
     *
     * Deleted persons are excluded: this relation is what the application reads, and a person
     * in the trash is not one of them. The delete guard below counts the pivot rows instead,
     * because a type may not be dropped while a deleted person still points at it.
     *
     * @return BelongsToMany<Person, $this>
     */
    public function persons(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'ENT_person_person_type', 'person_type_id', 'person_id')
            ->withTimestamps();
    }

    /**
     * A type that a person holds cannot be dropped, not even when that person is in the trash.
     * The permanent delete is refused by the foreign key of the pivot table; this hook covers the
     * soft delete, and takes the row lock in a transaction of its own that TransactionalDelete keeps
     * held until the delete is written, so a person attached in between makes the delete fail
     * instead of leaving a type in the trash that a person still points at.
     */
    /**
     * Check if the person type name is taken.
     */
    public static function nameIsTaken(string $name): bool
    {
        return static::where(function ($query) use ($name) {
            $query->where('name_eu', $name)
                ->orWhere('name_es', $name)
                ->orWhere('name_fr', $name)
                ->orWhere('name_en', $name);
        })->exists();
    }

    protected static function booted(): void
    {
        static::deleting(static function (self $personType): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($personType->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! DB::table('ENT_person_person_type')
                ->where('person_type_id', $locked->getKey())
                ->exists();
        });
    }
}
