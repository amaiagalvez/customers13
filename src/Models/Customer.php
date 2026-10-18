<?php

namespace entities13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use entities13\Policies\EntityPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use entities13\Database\Factories\EntityFactory;
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
    protected $table = 'CUM_entities';

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
        return $this->belongsToMany(EntityType::class, 'CUM_entity_entity_type', 'entity_id', 'entity_type_id')
            ->withTimestamps();
    }
}
