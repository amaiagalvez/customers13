@php
    $editingPersonType = $list['create'] && str_starts_with(old('_person_type_form', ''), 'edit-');
    $deletedPersonTypeConflict = session('deleted_person_type_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedPersonTypeConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingPersonType ? old('_person_type_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingPersonType ? old('_person_type_form') : 'create',
            'method' => $editingPersonType ? 'PUT' : 'POST',
            'action' => $editingPersonType
                ? route('person-types.update', old('_person_type_id'))
                : route('person-types.store'),
            'title' => $editingPersonType ? __('Edit person type') : __('New person type'),
            'submitLabel' => $editingPersonType ? __('Update person type') : __('Save person type'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('person-types.store')),
        updateUrl: @js(route('person-types.update', '__PERSON_TYPE__')),
        createPersonType() {
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
                title: @js(__('New person type')),
                submitLabel: @js(__('Save person type')),
            };
        },
        editPersonType(personType) {
            this.form = {
                ...personType,
                context: `edit-${personType.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__PERSON_TYPE__', personType.id),
                title: @js(__('Edit person type')),
                submitLabel: @js(__('Update person type')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'person-type-form' }))"
        @elseif ($deletedPersonTypeConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'person-type-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createPersonType(); $nextTick(() => $dispatch('modal-show', { name: 'person-type-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="person-type" :description="__('Types used to classify persons.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="person-type" :label="__('New person type')"
                        click="createPersonType()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="person-type" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$personTypes->total()" :scope="$scope">
                <x-basics13::list.table prefix="person-type" :paginator="$personTypes">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="person-type" :scope="$scope" />
                    </x-slot:toolbar>

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
                                        data-test="person-type-name-{{ $row['id'] }}">
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="person-type"
                                        edit-handler="editPersonType" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="person-type-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createPersonType()"
                                            data-test="person-type-empty-create">
                                            {{ __('New person type') }}
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
            <flux:modal name="person-type-form" aria-labelledby="person-type-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('entities13::person-types.form')
            </flux:modal>

            @if ($deletedPersonTypeConflict)
                <x-basics13::name-conflict-modal name="person-type-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedPersonTypeConflict['name_eu'],
                    ])" :create-action="route('person-types.store')" :restore-action="route(
                        'person-types.trash.restore',
                        $deletedPersonTypeConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new person type')" :restore-label="__('Restore the deleted record instead')"
                    create-test="person-type-conflict-create-new"
                    restore-test="person-type-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="person-type" />
    </div>
</x-layouts::app>