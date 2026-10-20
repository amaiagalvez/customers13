<?php

namespace Entities13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Entities13\Policies\EntityTypePolicy;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Entities13\Database\Factories\EntityTypeFactory;
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
#[UsePolicy(EntityTypePolicy::class)]
#[UseFactory(EntityTypeFactory::class)]
class EntityType extends Model
{
    /** @use HasFactory<EntityTypeFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ENT_entity_types';

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
     * The entities holding this type.
     *
     * Deleted entities are excluded: this relation is what the application reads, and a entity
     * in the trash is not one of them. The delete guard below counts the pivot rows instead,
     * because a type may not be dropped while a deleted entity still points at it.
     *
     * @return BelongsToMany<Entity, $this>
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'ENT_entity_entity_type', 'entity_type_id', 'entity_id')
            ->withTimestamps();
    }

    /**
     * A type that a entity holds cannot be dropped, not even when that entity is in the trash.
     * The permanent delete is refused by the foreign key of the pivot table; this hook covers the
     * soft delete, and takes the row lock in a transaction of its own that TransactionalDelete keeps
     * held until the delete is written, so a entity attached in between makes the delete fail
     * instead of leaving a type in the trash that a entity still points at.
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $entityType): bool {
            $locked = DB::transaction(static fn (): self => self::withTrashed()
                ->whereKey($entityType->getKey())
                ->lockForUpdate()
                ->firstOrFail());

            return ! DB::table('ENT_entity_entity_type')
                ->where('entity_type_id', $locked->getKey())
                ->exists();
        });
    }
}
