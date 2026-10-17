<?php

namespace Customers13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Customers13\Policies\CustomerPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Customers13\Database\Factories\CustomerFactory;
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
#[UsePolicy(CustomerPolicy::class)]
#[UseFactory(CustomerFactory::class)]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

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

    /**
     * The types this customer is categorised with. A customer holds as many as it needs, and the
     * pivot row survives the customer's soft delete so the type stays in use while it is in the
     * trash.
     *
     * @return BelongsToMany<CustomerType, $this>
     */
    public function customerTypes(): BelongsToMany
    {
        return $this->belongsToMany(CustomerType::class, 'CUM_customer_customer_type', 'customer_id', 'customer_type_id')
            ->withTimestamps();
    }
}
