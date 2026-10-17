<?php

namespace Customers13\Transformers;

use Customers13\Models\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<CustomerType>
 */
class CustomerTypeListTransformer extends ListTransformer
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'nameEu' => $record->name_eu,
            'nameEs' => $record->name_es,
            'nameFr' => $record->name_fr,
            'nameEn' => $record->name_en,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A type that a customer holds is archived instead of deleted: it stays selectable in the
     * customer form, which is where the type gets used from. The count is taken with the deleted
     * customers in, because those keep the type too.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('customers_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('customers_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    protected function getExtraDate(Model $record, string $scope): ?string
    {
        return match ($scope) {
            'active' => $record->created_at?->toIso8601String(),
            'archived' => $record->updated_at?->toIso8601String(),
            'trash' => $record->deleted_at?->toIso8601String(),
        };
    }

    protected function getRestoreActive(Model $record): bool
    {
        return $record->active;
    }

    /**
     * @param  CustomerType  $record
     * @return array{id: int, name_eu: string, name_es: string|null, name_fr: string|null, name_en: string|null, notes: string|null}
     */
    protected function editPayload(Model $record): array
    {
        return $record->only(['id', 'name_eu', 'name_es', 'name_fr', 'name_en', 'notes']);
    }

    protected function resourceLabel(): string
    {
        return __('Customer types');
    }

    protected function noMatchMessage(): string
    {
        return __('No customer types match your search.');
    }

    protected function noRecordsMessage(): string
    {
        return __('No customer types yet.');
    }

    protected function resourceKey(): string
    {
        return 'customer-type';
    }

    /**
     * @return array{active: string, archived: string, trash: string, destroy: string, archive: string, activate: string, restore: string, trashDestroy: string}
     */
    protected function routes(): array
    {
        return [
            'active' => 'customer-types.index',
            'archived' => 'customer-types.archived.index',
            'trash' => 'customer-types.trash.index',
            'destroy' => 'customer-types.destroy',
            'archive' => 'customer-types.archive',
            'activate' => 'customer-types.archived.activate',
            'restore' => 'customer-types.trash.restore',
            'trashDestroy' => 'customer-types.trash.destroy',
        ];
    }
}
