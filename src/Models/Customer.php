<?php

namespace Customers13\Models;

use Carbon\CarbonImmutable;
use Customers13\Policies\CustomerPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Database\Factories\CustomerFactory as CustomerFactoryBasics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns;

    /**
     * @var array{active: bool}
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * @var array{name: string, notes: string|null}
     */
    #[Fillable(['name', 'notes'])]
    protected $fillable = ['name', 'notes'];

    /**
     * The table used by the model.
     *
     * @var string
     */
    protected $table = 'CUM_customers';

    /**
     * @var array{active: 'boolean'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\Customers13\Models\Project::class);
    }
}