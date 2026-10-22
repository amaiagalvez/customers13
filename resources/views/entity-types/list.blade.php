@php
    $editingEntityType = $list['create'] && str_starts_with(old('_entity_type_form', ''), 'edit-');
    $deletedEntityTypeConflict = session('deleted_entity_type_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedEntityTypeConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingEntityType ? old('_entity_type_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingEntityType ? old('_entity_type_form') : 'create',
            'method' => $editingEntityType ? 'PUT' : 'POST',
            'action' => $editingEntityType
                ? route('entity-types.update', old('_entity_type_id'))
                : route('entity-types.store'),
            'title' => $editingEntityType ? __('Edit entity type') : __('New entity type'),
            'submitLabel' => $editingEntityType ? __('Update entity type') : __('Save entity type'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('entity-types.store')),
        updateUrl: @js(route('entity-types.update', '__ENTITY_TYPE__')),
        createEntityType() {
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
                title: @js(__('New entity type')),
                submitLabel: @js(__('Save entity type')),
            };
        },
        editEntityType(entityType) {
            this.form = {
                ...entityType,
                context: `edit-${entityType.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__ENTITY_TYPE__', entityType.id),
                title: @js(__('Edit entity type')),
                submitLabel: @js(__('Update entity type')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'entity-type-form' }))"
        @elseif ($deletedEntityTypeConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'entity-type-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createEntityType(); $nextTick(() => $dispatch('modal-show', { name: 'entity-type-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="entity-type" :description="__('Types used to classify entities.')" :scope="$scope">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="entity-type" :label="__('New entity type')"
                        click="createEntityType()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="entity-type" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$entityTypes->total()" :scope="$scope">
                <x-basics13::list.search :search="$list['search']" prefix="entity-type" />

                <x-basics13::list.table prefix="entity-type" :paginator="$entityTypes">
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
                                    <span class="block max-w-full truncate" title="{{ $row['nameEu'] }}"
                                        data-test="entity-type-name-{{ $row['id'] }}">
                                        {{ $row['nameEu'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEs'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameFr'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEn'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="entity-type"
                                        edit-handler="editEntityType" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="entity-type-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createEntityType()"
                                            data-test="entity-type-empty-create">
                                            {{ __('New entity type') }}
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
            <flux:modal name="entity-type-form" aria-labelledby="entity-type-form-heading"
                variant="flyout" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('entities13::entity-types.form')
            </flux:modal>

            @if ($deletedEntityTypeConflict)
                <x-basics13::name-conflict-modal name="entity-type-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedEntityTypeConflict['name_eu'],
                    ])" :create-action="route('entity-types.store')" :restore-action="route(
                        'entity-types.trash.restore',
                        $deletedEntityTypeConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new entity type')" :restore-label="__('Restore the deleted record instead')"
                    create-test="entity-type-conflict-create-new"
                    restore-test="entity-type-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="entity-type" />
    </div>
</x-layouts::app>