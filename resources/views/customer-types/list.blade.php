@php
    $editingCustomerType = $list['create'] && str_starts_with(old('_customer_type_form', ''), 'edit-');
    $deletedCustomerTypeConflict = session('deleted_customer_type_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedCustomerTypeConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingCustomerType ? old('_customer_type_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingCustomerType ? old('_customer_type_form') : 'create',
            'method' => $editingCustomerType ? 'PUT' : 'POST',
            'action' => $editingCustomerType
                ? route('customer-types.update', old('_customer_type_id'))
                : route('customer-types.store'),
            'title' => $editingCustomerType ? __('Edit customer type') : __('New customer type'),
            'submitLabel' => $editingCustomerType ? __('Update customer type') : __('Save customer type'),
        ]
        : null;
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('customer-types.store')),
        updateUrl: @js(route('customer-types.update', '__CUSTOMER_TYPE__')),
        createCustomerType() {
            this.form = {
                id: null,
                name_eu: '',
                name_es: '',
                name_fr: '',
                name_en: '',
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New customer type')),
                submitLabel: @js(__('Save customer type')),
            };
        },
        editCustomerType(customerType) {
            this.form = {
                ...customerType,
                context: `edit-${customerType.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__CUSTOMER_TYPE__', customerType.id),
                title: @js(__('Edit customer type')),
                submitLabel: @js(__('Update customer type')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'customer-type-form' }))"
        @elseif ($deletedCustomerTypeConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'customer-type-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createCustomerType(); $nextTick(() => $dispatch('modal-show', { name: 'customer-type-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="customer-type">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="customer-type" :label="__('New customer type')"
                        click="createCustomerType()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="customer-type" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$customerTypes->total()">
                <x-basics13::list.search :search="$list['search']" prefix="customer-type" />

                <x-basics13::list.table prefix="customer-type" :paginator="$customerTypes">
                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name (eu)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (es)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (fr)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (en)') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <flux:modal.trigger name="customer-type-form">
                                        <button type="button"
                                            class="block max-w-full truncate text-start font-medium text-zinc-900 hover:underline dark:text-zinc-100"
                                            x-on:click="editCustomerType(JSON.parse($el.dataset.payload))"
                                            data-payload="{{ json_encode($row['editPayload']) }}"
                                            title="{{ $row['nameEu'] }}"
                                            data-test="customer-type-name-eu-{{ $row['id'] }}">{{ $row['nameEu'] }}</button>
                                    </flux:modal.trigger>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate text-zinc-700 dark:text-zinc-300"
                                        title="{{ $row['nameEs'] }}"
                                        data-test="customer-type-name-es-{{ $row['id'] }}">{{ $row['nameEs'] ?? '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate text-zinc-700 dark:text-zinc-300"
                                        title="{{ $row['nameFr'] }}"
                                        data-test="customer-type-name-fr-{{ $row['id'] }}">{{ $row['nameFr'] ?? '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate text-zinc-700 dark:text-zinc-300"
                                        title="{{ $row['nameEn'] }}"
                                        data-test="customer-type-name-en-{{ $row['id'] }}">{{ $row['nameEn'] ?? '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="customer-type"
                                        edit-handler="editCustomerType" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && $list['search']['value'] === '')
                                    <flux:modal.trigger name="customer-type-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createCustomerType()"
                                            data-test="customer-type-empty-create">
                                            {{ __('New customer type') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </x-basics13::list.empty-state>
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="customer-type-form" aria-labelledby="customer-type-form-heading"
                variant="flyout" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('customers13::customer-types.form')
            </flux:modal>

            @if ($deletedCustomerTypeConflict)
                <x-basics13::name-conflict-modal name="customer-type-name-conflict"
                    :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedCustomerTypeConflict['name_eu'],
                    ])" :create-action="route('customer-types.store')" :restore-action="route('customer-types.trash.restore', $deletedCustomerTypeConflict['id'])"
                    :create-fields="$conflictCreateFields" :create-label="__('Create a new customer type')"
                    :restore-label="__('Restore the deleted record instead')"
                    create-test="customer-type-conflict-create-new" restore-test="customer-type-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="customer-type" />
    </div>
</x-layouts::app>
