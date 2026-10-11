<?php

namespace Customers13\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Customers13\Policies\CustomerPolicy;
use Basics13\Concerns\TracksAuditColumns;
use Illuminate\Database\Eloquent\SoftDeletes;
use Customers13\Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

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

    /**
     * Soft deletes inside a transaction on the model's own connection.
     *
     * The transaction itself belongs to no rule: what it buys is that a guard registered by an
     * optional integration package —which refuses the delete while the customer has children, and
     * locks the row to decide— keeps its lock from the guard's own inner transaction until the
     * soft delete is actually written. Without this, a child created between the guard's check and
     * the delete would slip through on any path that does not open a transaction itself, including
     * a plain `$customer->delete()` outside a controller.
     *
     * @return bool|null
     */
    public function delete()
    {
        return $this->getConnection()->transaction(fn (): ?bool => parent::delete());
    }
}
