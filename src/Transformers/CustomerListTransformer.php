<?php

namespace Customers13\Transformers;

use Customers13\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;
use Customers13\Contracts\CustomerIntegration;

/**
 * @extends ListTransformer<Customer>
 */
class CustomerListTransformer extends ListTransformer
{
    /**
     * Row key an integration sets when something hangs from the customer, which is what turns the
     * delete action into an archive one. Optional: without it the row is always deletable.
     */
    private const BLOCKS_DELETE = 'projects_exists';

    public function __construct(
        private readonly ?CustomerIntegration $integration = null,
    ) {}

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        /** @var Customer $record */
        return [
            'id' => $record->id,
            'name' => $record->name,
            'types' => $record->customerTypes->map(fn ($type) => [
                'name' => $type->name_eu,
                'active' => $type->active,
            ])->all(),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    protected function getDeleteBlockCondition(Model $record): bool
    {
        $extra = $this->integrationRow($record);

        return $extra[self::BLOCKS_DELETE] ?? false;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return '';
    }

    protected function integrationRow(Model $record): array
    {
        return $this->integration?->row($record) ?? [];
    }

    /**
     * The type ids come back as strings because that is what an HTML `<option>` value is: the
     * drawer binds the multi-select with x-model, which compares the option values by identity.
     *
     * @return array{id: int, name: string, notes: string|null, customer_type_ids: list<string>}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'notes']),
            'customer_type_ids' => array_values($record->customerTypes
                ->pluck('id')
                ->map(static fn (int $id): string => (string) $id)
                ->all()),
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Customers');
    }

    protected function resourceKey(): string
    {
        return 'customer';
    }
}
