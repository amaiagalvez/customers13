@php
    $editingEntity = $list['create'] && str_starts_with(old('_entity_form', ''), 'edit-');
    $deletedEntityConflict = session('deleted_entity_conflict');
    $initialForm = $list['create']
        ? [
            'id' => $editingEntity ? old('_entity_id') : null,
            'name' => old('name', ''),
            'notes' => old('notes', ''),
            'entity_type_ids' => old('entity_type_ids', []),
            'context' => $editingEntity ? old('_entity_form') : 'create',
            'method' => $editingEntity ? 'PUT' : 'POST',
            'action' => $editingEntity
                ? route('entities.update', old('_entity_id'))
                : route('entities.store'),
            'title' => $editingEntity ? __('Edit entity') : __('New entity'),
            'submitLabel' => $editingEntity ? __('Update entity') : __('Save entity'),
        ]
        : null;

    // Get searchable fields for advanced search modal
    $searchableFields = $searchableFields ?? [];
    $filters = $filters ?? [];
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('entities.store')),
        updateUrl: @js(route('entities.update', '__ENTITY__')),
        createEntity() {
            this.form = {
                id: null,
                name: '',
                notes: '',
                entity_type_ids: [],
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New entity')),
                submitLabel: @js(__('Save entity')),
            };
        },
        editEntity(entity) {
            this.form = {
                ...entity,
                context: `edit-${entity.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__ENTITY__', entity.id),
                title: @js(__('Edit entity')),
                submitLabel: @js(__('Update entity')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'entity-form' }))"
        @elseif ($deletedEntityConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'entity-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createEntity(); $nextTick(() => $dispatch('modal-show', { name: 'entity-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="entity" :description="__('Your entities and their types.')" :scope="$scope" :searchableFields="$searchableFields" :filters="$filters">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="entity" :label="__('New entity')"
                        click="createEntity()" />
                @endif
                <x-basics13::list.excel-export
                    prefix="entity"
                    :route="route('entities.export')"
                    :columnDefinitions="[
                        ['key' => 'id', 'label' => __('ID')],
                        ['key' => 'name', 'label' => __('Name')],
                        ['key' => 'notes', 'label' => __('Notes')],
                        ['key' => 'active', 'label' => __('Status'), 'transformer' => fn ($e) => $e->active ? __('basics13::messages.active_label') : __('basics13::messages.inactive_label')],
                        ['key' => 'created_at', 'label' => __('Created at')],
                    ]"
                />
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="entity" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$entities->total()" :scope="$scope" :filters="$filters">
                <x-basics13::list.search :search="$list['search']" prefix="entity" />

                <x-basics13::list.table prefix="entity" :paginator="$entities">
                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Types') }}</flux:table.column>
                        @includeIf(config('entities13.views.entity_list.columns'))
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50 group">
                                <flux:table.cell>
                                    <span class="font-medium block truncate" title="{{ $row['name'] }}"
                                        data-test="entity-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="flex items-center gap-2">
                                    @if ($row['types'] === [])
                                        <span class="text-zinc-500 capitalize text-sm"
                                            data-test="entity-types-{{ $row['id'] }}">—</span>
                                    @else
                                        <span class="flex flex-wrap gap-1"
                                            data-test="entity-types-{{ $row['id'] }}">
                                            @foreach ($row['types'] as $type)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:badge size="sm">
                                                        {{ $type['name'] }}
                                                    </flux:badge>
                                                    @unless ($type['active'])
                                                        <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                            {{ __('basics13::messages.archived_label') }}
                                                        </span>
                                                    @endunless
                                                </span>
                                            @endforeach
                                        </span>
                                    @endif
                                </flux:table.cell>
                                @includeIf(config('entities13.views.entity_list.cells'))
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900 px-2">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="entity"
                                        edit-handler="editEntity" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="3 + (int) config('entities13.views.entity_list.count', 0)" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="entity-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createEntity()" data-test="entity-empty-create">
                                            {{ __('New entity') }}
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
            <flux:modal name="entity-form" aria-labelledby="entity-form-heading" flyout variant="floating"
                position="right"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('entities13::entities.form')
            </flux:modal>

            @if ($deletedEntityConflict)
                <x-basics13::name-conflict-modal name="entity-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedEntityConflict['name'],
                    ])" :create-action="route('entities.store')" :restore-action="route('entities.trash.restore', $deletedEntityConflict['id'])" :create-fields="['name' => old('name', $deletedEntityConflict['name'])]"
                    :create-label="__('Create a new entity')" :restore-label="__('Restore the deleted record instead')" create-test="entity-conflict-create-new"
                    restore-test="entity-conflict-restore" />
            @endif
        @endif

        <livewire:basics13.advanced-search-modal
            :fields="$searchableFields"
            :filters="$filters"
            :scope="$scope" />

        <x-basics13::list.confirm-modal prefix="entity" />
    </div>
</x-layouts::app>