<?php

namespace Customers13\Models;

use App\Models\Epic;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Customers13\Policies\CustomerPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Illuminate\Database\Eloquent\SoftDeletes;
use Customers13\Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Carbon|null $deleted_at
 * @property-read bool $projects_exists
 * @property-read int $projects_count
 * @property-read int $epics_count
 * @property-read int $comments_count
 */
#[Fillable(['name', 'notes'])]
#[UsePolicy(CustomerPolicy::class)]
#[UseFactory(CustomerFactory::class)]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'CUM_customers';

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

    public function delete(): ?bool
    {
        return $this->getConnection()->transaction(fn (): ?bool => parent::delete());
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Epics reached through the projects of this customer; trashed projects are excluded.
     *
     * @return HasManyThrough<Epic, Project, $this>
     */
    public function epics(): HasManyThrough
    {
        return $this->hasManyThrough(Epic::class, Project::class, 'customer_id', 'project_id');
    }
}
