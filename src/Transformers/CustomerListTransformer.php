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

    public function __construct(private readonly CustomerIntegration $integration) {}

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    protected function getDeleteBlockCondition(Model $record): bool
    {
        $extra = $this->integration->row($record);

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

    protected function getExtraDate(Model $record, string $scope): ?string
    {
        return match ($scope) {
            'active' => $record->created_at?->toIso8601String(),
            'archived' => $record->updated_at?->toIso8601String(),
            'trash' => $record->deleted_at?->toIso8601String(),
        };
    }

    protected function integrationRow(Model $record): array
    {
        return $this->integration->row($record);
    }

    protected function getRestoreActive(Model $record): bool
    {
        return $record->active;
    }

    /**
     * @param  Customer  $record
     * @return array{id: int, name: string, notes: string|null}
     */
    protected function editPayload(Model $record): array
    {
        return $record->only(['id', 'name', 'notes']);
    }

    protected function resourceLabel(): string
    {
        return __('Customers');
    }

    protected function noMatchMessage(): string
    {
        return __('No customers match your search.');
    }

    protected function noRecordsMessage(): string
    {
        return __('No customers yet.');
    }

    protected function resourceKey(): string
    {
        return 'customer';
    }

    /**
     * @return array{active: string, archived: string, trash: string, destroy: string, archive: string, activate: string, restore: string, trashDestroy: string}
     */
    protected function routes(): array
    {
        return [
            'active' => 'customers.index',
            'archived' => 'customers.archived.index',
            'trash' => 'customers.trash.index',
            'destroy' => 'customers.destroy',
            'archive' => 'customers.archive',
            'activate' => 'customers.archived.activate',
            'restore' => 'customers.trash.restore',
            'trashDestroy' => 'customers.trash.destroy',
        ];
    }
}
