<?php

namespace Entities13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Entities13\Policies\EntityPolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Entities13\Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 */
#[Fillable(['name', 'notes'])]
#[UsePolicy(EntityPolicy::class)]
#[UseFactory(EntityFactory::class)]
class Entity extends Model
{
    /** @use HasFactory<EntityFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ENT_entities';

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
     * The types this entity is categorised with. A entity holds as many as it needs, and the
     * pivot row survives the entity's soft delete so the type stays in use while it is in the
     * trash.
     *
     * @return BelongsToMany<EntityType, $this>
     */
    public function entityTypes(): BelongsToMany
    {
        return $this->belongsToMany(EntityType::class, 'ENT_entity_entity_type', 'entity_id', 'entity_type_id')
            ->withTimestamps();
    }

    /**
     * The persons linked to this entity.
     *
     * Deleted persons are excluded by default; the delete guard counts with trashed included.
     *
     * @return HasMany<Person, $this>
     */
    public function persons(): HasMany
    {
        return $this->hasMany(Person::class, 'entity_id');
    }

    /**
     * An entity that has persons cannot be dropped, not even when those persons are in the trash.
     * The permanent delete is refused by the foreign key of the persons table; this hook covers the
     * soft delete, and takes the row lock in a transaction of its own that TransactionalDelete keeps
     * held until the delete is written, so a person attached in between makes the delete fail
     * instead of leaving an entity in the trash that a person still points at.
     */
    protected static function booted(): void
    {
        // Returning nothing when the delete may go ahead lets the other deleting listeners run:
        // the first non-null answer stops the event.
        static::deleting(static function (self $entity): ?bool {
            // $entity is already locked by TransactionalDelete; no need to reload
            return DB::table('ENT_persons')->where('entity_id', $entity->getKey())->exists() ? false : null;
        });
    }
}
