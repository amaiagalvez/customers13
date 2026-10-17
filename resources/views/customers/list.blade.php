@php
    $editingCustomer = $list['create'] && str_starts_with(old('_customer_form', ''), 'edit-');
    $deletedCustomerConflict = session('deleted_customer_conflict');
    $initialForm = $list['create']
        ? [
            'id' => $editingCustomer ? old('_customer_id') : null,
            'name' => old('name', ''),
            'notes' => old('notes', ''),
            'customer_type_ids' => old('customer_type_ids', []),
            'context' => $editingCustomer ? old('_customer_form') : 'create',
            'method' => $editingCustomer ? 'PUT' : 'POST',
            'action' => $editingCustomer
                ? route('customers.update', old('_customer_id'))
                : route('customers.store'),
            'title' => $editingCustomer ? __('Edit customer') : __('New customer'),
            'submitLabel' => $editingCustomer ? __('Update customer') : __('Save customer'),
        ]
        : null;
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('customers.store')),
        updateUrl: @js(route('customers.update', '__CUSTOMER__')),
        createCustomer() {
            this.form = {
                id: null,
                name: '',
                notes: '',
                customer_type_ids: [],
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New customer')),
                submitLabel: @js(__('Save customer')),
            };
        },
        editCustomer(customer) {
            this.form = {
                ...customer,
                context: `edit-${customer.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__CUSTOMER__', customer.id),
                title: @js(__('Edit customer')),
                submitLabel: @js(__('Update customer')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'customer-form' }))"
        @elseif ($deletedCustomerConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'customer-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createCustomer(); $nextTick(() => $dispatch('modal-show', { name: 'customer-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="customer">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="customer" :label="__('New customer')"
                        click="createCustomer()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="customer" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$customers->total()">
                <x-basics13::list.search :search="$list['search']" prefix="customer" />

                <x-basics13::list.table prefix="customer" :paginator="$customers">
                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Types') }}</flux:table.column>
                        @includeIf(config('customers13.views.customer_list.columns'))
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <flux:modal.trigger name="customer-form">
                                        <button type="button"
                                            class="block max-w-full truncate text-start font-medium text-zinc-900 hover:underline dark:text-zinc-100"
                                            x-on:click="editCustomer(JSON.parse($el.dataset.payload))"
                                            data-payload="{{ json_encode($row['editPayload']) }}"
                                            title="{{ $row['name'] }}"
                                            data-test="customer-name-{{ $row['id'] }}">{{ $row['name'] }}</button>
                                    </flux:modal.trigger>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    @if ($row['types'] === [])
                                        <span class="text-zinc-400 dark:text-zinc-500"
                                            data-test="customer-types-{{ $row['id'] }}">—</span>
                                    @else
                                        <span class="flex flex-wrap gap-1"
                                            data-test="customer-types-{{ $row['id'] }}">
                                            @foreach ($row['types'] as $type)
                                                <flux:badge size="sm">{{ $type }}</flux:badge>
                                            @endforeach
                                        </span>
                                    @endif
                                </flux:table.cell>
                                @includeIf(config('customers13.views.customer_list.cells'))
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="customer"
                                        edit-handler="editCustomer" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :colspan="3 + (int) config('customers13.views.customer_list.count', 0)" :message="$list['emptyMessage']">
                                @if ($list['create'] && $list['search']['value'] === '')
                                    <flux:modal.trigger name="customer-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createCustomer()"
                                            data-test="customer-empty-create">
                                            {{ __('New customer') }}
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
            <flux:modal name="customer-form" aria-labelledby="customer-form-heading"
                variant="flyout" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('customers13::customers.form')
            </flux:modal>

            @if ($deletedCustomerConflict)
                <x-basics13::name-conflict-modal name="customer-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedCustomerConflict['name'],
                    ])" :create-action="route('customers.store')" :restore-action="route('customers.trash.restore', $deletedCustomerConflict['id'])" :create-fields="['name' => old('name', $deletedCustomerConflict['name'])]"
                    :create-label="__('Create a new customer')" :restore-label="__('Restore the deleted record instead')" create-test="customer-conflict-create-new"
                    restore-test="customer-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="customer" />
    </div>
</x-layouts::app>