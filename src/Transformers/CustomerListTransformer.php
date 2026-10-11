<?php

namespace Customers13\Transformers;

use Customers13\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;
use Customers13\Contracts\CustomerIntegration;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * @param  LengthAwarePaginator<int, Customer>  $customers
     * @param  array{active: int, archived: int, trashed: int}|null  $counts
     * @return array<string, mixed>
     */
    public function active(LengthAwarePaginator $customers, string $search, ?array $counts = null): array
    {
        return [
            ...$this->envelope('active', $search, $counts),
            'rows' => collect($customers->items())->map(function (Customer $customer): array {
                $extra = $this->integration->row($customer);

                return [
                    ...$this->columns($customer, $extra),
                    'extraDate' => $customer->created_at?->toIso8601String(),
                    'actions' => [
                        $this->editAction($customer),
                        ($extra[self::BLOCKS_DELETE] ?? false)
                            ? $this->archiveAction($customer, __('basics13::messages.archive_instead_of_delete'))
                            : $this->deleteAction($customer),
                    ],
                ];
            })->all(),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, Customer>  $customers
     * @param  array{active: int, archived: int, trashed: int}|null  $counts
     * @return array<string, mixed>
     */
    public function archived(LengthAwarePaginator $customers, string $search, ?array $counts = null): array
    {
        return [
            ...$this->envelope('archived', $search, $counts),
            'rows' => collect($customers->items())->map(fn (Customer $customer): array => [
                ...$this->columns($customer, $this->integration->row($customer)),
                'extraDate' => $customer->updated_at?->toIso8601String(),
                'actions' => [$this->activateAction($customer)],
            ])->all(),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, Customer>  $customers
     * @param  array{active: int, archived: int, trashed: int}|null  $counts
     * @return array<string, mixed>
     */
    public function trash(LengthAwarePaginator $customers, string $search, ?array $counts = null): array
    {
        return [
            ...$this->envelope('trash', $search, $counts),
            'rows' => collect($customers->items())->map(fn (Customer $customer): array => [
                ...$this->columns($customer, $this->integration->row($customer)),
                'extraDate' => $customer->deleted_at?->toIso8601String(),
                'actions' => [
                    $this->restoreAction($customer, $customer->active),
                    $this->forceDeleteAction($customer),
                ],
            ])->all(),
        ];
    }

    /**
     * The integration's fields are merged last so it can override anything the base put here,
     * which is the only way a row can carry a column the base knows nothing about.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function columns(Customer $customer, array $extra): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'editPayload' => $this->editPayload($customer),
            ...$extra,
        ];
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
